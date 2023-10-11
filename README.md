Backup Symfony
==============



Installation
------------

BackupSymfonyBundle requires PHP 8 or higher and Symfony 5.4 or higher. 

Run the following command to install it in your application:

```
$ composer require captjm/backup-symfony-bundle
```

Insert in `config/routes.yaml`:

```
captjm_backup:
    resource: '../vendor/captjm/backup-symfony-bundle/src/Controller/'
    type: annotation
```

Insert in `config/services.yaml`:

```
parameters:

    captjm.database_url: '%env(DATABASE_URL)%'
    
services:

    Captjm\BackupSymfonyBundle\Controller\CaptjmBackupSymfonyController:
        tags:
            - 'controller.service_arguments'
        arguments:
            - '@parameter_bag'
```

And then insert in `Controller/Admin/DashboardController.php`:

``` 
public function configureMenuItems(): iterable
{
    ....
    yield MenuItem::linkToRoute('Backup', 'fas fa-download', 'captjm_backup');
    ....
}    
```

Documentation
-------------


Versions
--------

Demo Application
----------------


License
-------

This software is published under the [MIT License](LICENSE.md)
