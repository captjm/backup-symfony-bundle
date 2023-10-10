<?php

namespace Captjm\BackupSymfonyBundle;

use Captjm\BackupSymfonyBundle\DependencyInjection\CaptjmBackupSymfonyExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CaptjmBackupSymfonyBundle extends Bundle
{
    public function getContainerExtension(): CaptjmBackupSymfonyExtension
    {
        return new CaptjmBackupSymfonyExtension();
    }
}