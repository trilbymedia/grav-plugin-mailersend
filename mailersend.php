<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Data\Data;
use Grav\Common\Data\ValidationException;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use League\HTMLToMarkdown\HtmlConverter;
use MailerSend\Exceptions\MailerSendException;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Helpers\Builder\Variable;
use MailerSend\MailerSend;
use Psr\Http\Client\ClientExceptionInterface;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\HttpClient\Exception\JsonException;

/**
 * Class MailerSendPlugin
 * @package Grav\Plugin
 */
class MailerSendPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onFormProcessed'      => ['onFormProcessed', 0],
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }
    }

    /**
     * Process `mailersend` form action
     *
     * @param Event $event
     * @return void
     * @throws MailerSendException
     * @throws \JsonException
     * @throws \MailerSend\Exceptions\MailerSendAssertException
     */
    public function onFormProcessed(Event $event): void
    {
        $form = $event['form'];
        $action = $event['action'];
        $params = $event['params'];

        if ($action === 'mailersend') {
            $this->debugLog('onFormProcessed: mailersend action triggered for form "' . $form->name() . '"');

            $vars = new Data([
                'form' => $form,
                'page' => $this->grav['page']
            ]);

            $this->grav->fireEvent('onMailerSendVars', new Event(['vars' => $vars]));
            $params = $this->processParams($params, $vars->toArray());

            $api_token = $this->config->get('plugins.mailersend.api_token');
            if (empty($api_token) || strlen($api_token) < 10) {
                $message = "Invalid or missing Mailersend API token";
                $this->grav->fireEvent('onFormValidationError', new Event([
                    'form' => $form,
                    'message' => $message
                ]));
                $event->stopPropagation();
                return;
            }

            $mailersend = new MailerSend(['api_key' => $api_token]);
            $emailParams = new EmailParams();

            try {
                $recipients = $this->processRecipients('to', $params);
                $emailParams->setRecipients($recipients);
                $emailParams->setCc($this->processRecipients('cc', $params));
                $emailParams->setBcc($this->processRecipients('bcc', $params));

                $emailParams->setSubject($params['subject'] ?? $this->grav['page']->title() ?? $this->config->get('site.title'));

                $from_all = $this->processRecipients('from', $params);
                if (is_array($from_all)) {
                    $from = array_shift($from_all)->toArray();
                    $emailParams->setFrom($from['email'])
                                ->setFromName($from['name']);
                }

                if (isset($params['reply_to'])) {
                    $reply_to = $this->createRecipient($params['reply_to'])->toArray();
                    $emailParams->setReplyTo($reply_to['email'])
                                ->setReplyToName($reply_to['name']);
                }

                if (isset($params['template_id'])) {
                    $to = array_shift($recipients)->toArray();
                    $vars = $params['substitutions'] ?? [];
                    $variables = [new Variable($to['email'], $vars)];
                    $emailParams->setTemplateId($params['template_id'])->setVariables($variables);

                } else {
                    $message = $params['body'] ?? $params['message'] ?? $form->value('message') ?? '';
                    $html = (bool) ($params['html'] ?? false);
                    if ($html) {
                        $converter = new HtmlConverter();
                        $html = html_entity_decode($message);
                        $text = $converter->convert($html);
                        $emailParams->setHtml($html);
                        $emailParams->setText($text);
                    } else {
                        $emailParams->setText($message);
                    }
                }

                $this->grav->fireEvent('onMailerSendBeforeSend', new Event(['mailersend' => $mailersend, 'params' => $emailParams]));

                $debug = (bool) $this->config->get('plugins.mailersend.debug');
                $dry_run = (bool) $this->config->get('plugins.mailersend.dry_run');

                if ($debug) {
                    $data = [
                        'from' => $emailParams->getFrom(),
                        'from_name' => $emailParams->getFromName(),
                        'reply_to' => $emailParams->getReplyTo(),
                        'reply_to_name' => $emailParams->getReplyToName(),
                        'recipients' => $emailParams->getRecipients(),
                        'subject' => $emailParams->getSubject(),
                        'html' => $emailParams->getHtml(),
                        'text' => $emailParams->getText(),
                    ];

                    $this->debugLog('Composed email: ' . json_encode($data));
                }

                if ($dry_run) {
                    // Dry-run: do not actually send, just record that we would have
                    $this->debugLog('DRY-RUN enabled, email NOT sent.');
                } else {
                    $response = $mailersend->email->send($emailParams);

                    if ($debug) {
                        $status = is_array($response) ? ($response['status_code'] ?? null) : null;
                        $message_id = is_array($response) ? ($response['headers']['x-message-id'][0] ?? null) : null;
                        $this->debugLog('API response: status=' . json_encode($status) . ' message_id=' . json_encode($message_id));

                        // Surface any MailerSend warnings (e.g. ALL_SUPPRESSED / hard_bounced),
                        // which return a 202 but mean the email was NOT actually delivered.
                        $warnings = is_array($response) ? ($response['body']['warnings'] ?? null) : null;
                        if (!empty($warnings)) {
                            $this->debugLog('API warnings: ' . json_encode($warnings));
                        }
                    }
                }

            } catch (\Exception $e) {
                // Always record the real reason a send failed so it is not silently swallowed
                $error = 'MailerSend send failed: ' . get_class($e) . ' - ' . $e->getMessage();
                $this->grav['log']->error($error);
                $this->debugLog($error);
                if (method_exists($e, 'getResponse')) {
                    $this->debugLog('Error response: ' . json_encode($e->getResponse()));
                }

                $this->grav->fireEvent('onFormValidationError', new Event([
                    'form' => $form,
                    'message' => $e->getMessage()
                ]));

                $event->stopPropagation();
            }

            $this->grav->fireEvent('onMailerSendAfterSend', new Event(['mailersend' => $mailersend]));
        }
    }

    /**
     * Write a diagnostic line to logs/mailersend.log when debug is enabled.
     *
     * Keeps MailerSend diagnostics out of the noisy grav.log and only writes
     * anything while `debug` is turned on, so it is silent in normal operation.
     *
     * @param string $message
     * @return void
     */
    protected function debugLog(string $message): void
    {
        if (!$this->config->get('plugins.mailersend.debug')) {
            return;
        }

        static $logger = null;
        if ($logger === null) {
            $logfile = $this->grav['locator']->findResource('log://mailersend.log', true, true);
            $logger = new \Monolog\Logger('mailersend');
            $logger->pushHandler(new \Monolog\Handler\StreamHandler($logfile, \Monolog\Logger::DEBUG));
        }

        $logger->info($message);
    }

    /**
     * Iterate over recipient types
     *
     * @param string $type  to|from|reply_to|bcc|cc etc
     * @param array $params params in the mailersend `process:` for definition
     * @return array List of recipients
     * @throws \MailerSend\Exceptions\MailerSendAssertException
     */
    protected function processRecipients(string $type, array $params)
    {
        $recipients = $params[$type] ??
                      $this->config->get('plugins.mailersend.defaults.'.$type) ??
                      $this->config->get('plugins.email.'.$type) ??
                      [];

        $list = [];

        if (!empty($recipients)) {
            if (is_array($recipients) && Utils::isAssoc($recipients)) {
                $list[] = $this->createRecipient($recipients);
            } else {
                if (is_array($recipients[0])) {
                    foreach ($recipients as $recipient) {
                        $list[] = $this->createRecipient($recipient);
                    }
                } else {
                    if (is_string($recipients) && Utils::contains($recipients, ',')) {
                        $recipients = array_map('trim', explode(',', $recipients));
                        foreach ($recipients as $recipient) {
                            $list[] = $this->createRecipient($recipient);
                        }
                    } else {
                        $list[] = $this->createRecipient($recipients);
                    }
                }
            }
        }

        return $list;
    }

    /**
     * Creates a MailerSend recipient object and suppots the following formats:
     *
     * - ['hello@yoursite.com', 'Your Name'] # simple **array**
     * - 'Your Name <hello@yoursite.com>' # name-addr spec **string**
     * - ['hello@yoursite.com' => 'Your Name'] # basic associative **array**
     * - ['email' => 'hello@yoursite.com', 'name' => 'Your Name'] # full associative **array**
     * - 'hello@yoursite.com' # basic addr-spec **string**
     * - '<hello@yoursite.com>' # basic angle-addr **string**
     *
     * @param $data
     * @return Recipient
     * @throws \MailerSend\Exceptions\MailerSendAssertException
     */
    protected function createRecipient($data): Recipient
    {
        if (is_string($data)) {
            preg_match('/^(.*)\<(.*)\>$/', $data, $matches);
            if (isset($matches[2])) {
                $email = trim($matches[2]);
                $name = trim($matches[1]);
            } else {
                $email = $data;
                $name = null;
            }
        } elseif (Utils::isAssoc($data)) {
            $first_key = array_key_first($data);
            if (filter_var($first_key, FILTER_VALIDATE_EMAIL)) {
                $email = $first_key;
                $name = $data[$first_key];
            } else {
                $email = $data['email'] ?? $data['mail'] ?? $data['address'] ?? null;
                $name = $data['name'] ?? $data['fullname'] ?? null;
            }
        } else {
            $email = $data[0] ?? null;
            $name = $data[1] ?? null;
        }
        return new Recipient($email, $name);
    }

    /**
     * Twig Process each item in the params array
     *
     * @param array $params
     * @param array $vars
     * @return array
     */
    protected function processParams(array $params, array $vars = []): array
    {
        $twig = $this->grav['twig'];
        array_walk_recursive($params, function(&$value) use ($twig, $vars) {
            $value = $twig->processString($value, $vars);
        });
        return $params;
    }
}
