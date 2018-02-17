# Consultation management application

Web application that helps lectors easily manage their consultation appointments and let student to sign on those appointments.

## How does it work?

Application is meant to aid lectors with managing consultation appointments for their students or other users. App uses login system and users can register either as **lectors** or as **students** by filling up some basic info like name, e-mail address, etc. Created account is bound to provided e-mail address and requires e-mail verification.
For starters, registered lector has to create a consultation. Then others may look him up in the app and sign to his consultation. The app also provides many other useful functions to make the process even more comfortable.

#### Students can
- look up registered lectors
- view their and lectors' consultations (*and it's history*)
- sign in to consultations made by lectors (*also sign out*)
- set up notifications
- message other users

#### Lectors can
- **do all what students can**
- create, edit and manage consultatins

### Other features
- Look up lector by choosing him in dropdown list filtered by his name or e-mail or just part of it.
- Filter shown consultations by chosen date range or just view all of them from current date.
- Look in the past on what consultations have you been signed on.
- Each consultation has a history log where you can see what happened from it's creation.
- View who else is signed to a consultation and send them or a lector a message.
- Add/edit a note while signed to a consultation.
- Choose what notifications to receive if something happens whith a consultation (*student signs in/out, consultation gets deleted or prolonged, and more!*). Don't worry about receiving several notifications for example when some witty user sings in and out several times. In that case the app waits a few minutes and then sends you the final summary about what happened.
- Lector can write a note to a consultation when creating one. This note can be later edited, deleted or even created anew.
- Lector can restrict signing to his consultation only to users with specific e-mail address domain.
- Lector can duplicate existing consultation and edit just a few things instead of creating a new one and setting everything again.
- Lector can extend or shorten his consultation or just disable chosen sections (*while also unsign signed users in those sections*).
- Lector can send the same message to multiple users signed to his consultation.

## Installation
The application should be hosted on a **server** and requires at least **PHP** version **5.6** and **MySQL** database version **5.0**. You will need access to **cron** service in order for notifications to work properly. You will also have to create a new database that can usually be managed through your webhosing. Or you can use already existing one on your server. You will need the name of the database, username and password to access the database and hostname of your database server. You will also have to create new e-mail for application to send info and notifications from.
Several constants have to be redefined in `local_settings.php`.

| Constant | Meaning |
|----|----|
| `db_servername` | address of your database server |
| `db_username` | username to access your database |
| `db_password` | password linked to username |
| `db_dbname` | name of your database |
| `HOME_URL` | URL address to access the `index.php` |
| `APP_EMAIL` | existing e-mail for app |
| `APP_NAME` | name of your app |
| `DEFAULT_LANG` | default language of the app (currently only `cze` or `eng`) |

Database tables will be created automatically if they don't already exist (which means if you decide for whatever reason to delete a table, it will be created anew when someone visits application page).

Next, you will need to set up a cron job (*or use other available method to execute a PHP script periodically*). If you wonder how to work with cron, you can start by looking at [this short tutorial](https://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/). The job we will need to execute periodically (*every 10, 20 or 30 minutes - select what suits you best*) is PHP script `cronscript.php`. You may need to specify that you want to run PHP command and you want it to execute file `cronscript.php`.
The mentioned script checks if notifications need to be sent and sends them if yes.

From now on, your application should be functional and ready to work!
