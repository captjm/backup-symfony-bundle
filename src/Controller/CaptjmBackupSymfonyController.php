<?php

namespace Captjm\BackupSymfonyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Form;

class CaptjmBackupSymfonyController extends AbstractController
{
    private string $backupsDirectory;
    private string $databaseUrl;
    private Filesystem $filesystem;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->filesystem = new Filesystem();
        $this->backupsDirectory = $parameterBag->get('kernel.project_dir') . '/var/backups';
        if (!$this->filesystem->exists($this->backupsDirectory)) {
            $this->filesystem->mkdir($this->backupsDirectory, 0755);
        }
        $this->databaseUrl = $parameterBag->get('captjm.database_url');
    }


    #[Route(path: 'admin/captjm/backup', name: 'captjm_backup_symfony')]
    public function backup(Request $request): Response
    {
        $form = $this->generateForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $backups = $form->getData()['backups'];
            if ($form->get('backup')->isClicked()) {
                $conf = parse_url($this->databaseUrl);
                $sqlFile = $this->backupsDirectory . DIRECTORY_SEPARATOR . 'db-' . date('Y-m-d-H-i-s') . '.sql';
                $dbName = trim($conf['path'], '/');
                $cmd = sprintf(
                    'mysqldump -h %s --port %s -u %s --password=%s %s --ignore-table=%s.user > %s',
                    $conf['host'], $conf['port'], $conf['user'], $conf['pass'], $dbName, $dbName, $sqlFile
                );
                $output = [];
                $exit_status = null;
                exec($cmd, $output, $exit_status);
                $form = $this->generateForm();
            } elseif ($form->get('download')->isClicked()) {
                if (count($backups) === 1) {
                    $responseFile = $backups[0];
                    $deleteFileAfterSend = false;
                } elseif (count($backups) > 1) {
                    $zip = new \ZipArchive();
                    $responseFile = 'db-backups-' . date('Y-m-d-H-i-s') . '.zip';
                    if ($zip->open($this->backupsDirectory . DIRECTORY_SEPARATOR . $responseFile,
                            \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                        foreach ($backups as $fileName) {
                            $zip->addFile($this->backupsDirectory . DIRECTORY_SEPARATOR . $fileName, $fileName);
                        }
                        $zip->close();
                    }
                    $deleteFileAfterSend = true;
                } else {
                    $responseFile = null;
                }
                if ($responseFile) {
                    $response = new BinaryFileResponse($this->backupsDirectory . DIRECTORY_SEPARATOR . $responseFile);
                    $response->headers->set('Content-Type', 'text/plain');
                    $response->headers->set('Content-Disposition',
                        sprintf('attachment; filename="%s"', $responseFile));
                    $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
                    $response->deleteFileAfterSend($deleteFileAfterSend);
                    return $response;
                }
            } elseif ($form->get('delete')->isClicked()) {
                foreach ($backups as $backup) {
                    $this->filesystem->remove($this->backupsDirectory . DIRECTORY_SEPARATOR . $backup);
                }
                $form = $this->generateForm();
            }
        }

        return $this->render('@CaptjmBackupSymfony/captjm_backup.html.twig', [
            'form' => $form,
        ]);
    }

    private function generateForm(): FormInterface
    {
        $choices = [];
        $finder = new Finder();
        $finder->files()->in($this->backupsDirectory)->name('*.sql');
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $choices[$file->getFilename()] = $file->getFilename();
            }
        }
        return $this->createFormBuilder()
            ->add('backups', ChoiceType::class,
                [
                    'choices' => $choices,
                    'expanded' => true,
                    'multiple' => true,
                ])
            ->add('backup', SubmitType::class)
            ->add('download', SubmitType::class)
            ->add('delete', SubmitType::class)
            ->getForm();
    }
}
