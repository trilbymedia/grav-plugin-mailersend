# MailerSend Plugin

A plugin for sending emails via forms using the MailerSend API.

The **MailerSend** Plugin is an extension for [Grav CMS](https://github.com/getgrav/grav).

NOTE: Currently this works via processing Grav forms only!

## Installation

Installing the MailerSend plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### GPM Installation (Preferred)

To install the plugin via the [GPM](https://learn.getgrav.org/cli-console/grav-cli-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install mailersend

This will install the MailerSend plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/mailersend`.

## Configuration

### Main plugin configuration options:

```yaml
enabled: true
api_token: <YOUR API TOKEN>
defaults:
  to: 
  from: 
  cc:
  bcc:
```

NOTE: You can create a custom permission token, but it at least needs full **Email** permissions. - https://www.mailersend.com/help/managing-api-tokens

### Form definition Example:

```yaml
form:
    name: test-form
    ...
    process:
        mailersend:
          subject: "[WEB Form] {{ form.value.name|e }}" # REQUIRED
          reply_to: ["{{ form.value.email }}", "{{ form.value.name|e }}"] # REQUIRED
          from: ["no-reply@yoursite.com", "Your Name"] # OPTIONAL: can use defaults if provided
          bcc: ["{{ form.value.email }}", "{{ form.value.name|e }}"] # OPTIONAL: can use defaults if provided
          to: ["contact@yoursite.com", "Your Name"] # OPTIONAL: can use defaults if provided
          html: true # OPTIONAL: will assume message is HTML and create a text version automatically
          message: "{{ form.value.message }}" # OPTIONAL: will use form.value.message if not set
          template_id: jy7zpl98ye345vx6 # OPTIONAL: will use this template ID if specified and messages is ignored
          substitutions: # OPTIONAL: only used when template_id is provided
            name: "{{ form.value.name|e }}"
            email: "{{ form.value.email }}"
            message: "{{ form.value.message|e }}"
```

You can use any value from the form via `{{ form.value.<field-attribute> }}` or from Grav's configuration via `{{ config.x.y.z }}`

#### YAML Email address formats: 

preferred: `[hello@yoursite.com, Your Name]`
alternative: `{email: hello@yoursite.com, name: Your Name}`
discouraged but works: `hello@yoursite.com`


