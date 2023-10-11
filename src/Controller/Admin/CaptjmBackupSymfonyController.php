<?php

namespace Captjm\BackupSymfonyBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

class CaptjmBackupSymfonyController extends AbstractController
{
    #[Route(name: 'captjm_backup_symfony')]
    public function dumpData(): Response
    {
        $sqlFile = $this->dumpDB();
        return $this->render('dump_data/dump.html.twig', [
            'fileName' => $sqlFile,
            'sql' => file_get_contents($sqlFile, false, null, 0, 800),
            'attachments' => $this->dumpAttachments(),
        ]);
    }

    private function dumpDB(): string
    {
        $conf = parse_url($this->getParameter('app.database_url'));
        $tmpDir = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'backups';
        $sqlFile = $tmpDir . DIRECTORY_SEPARATOR . 'db-' . date('Y-m-d-H-i-s') . '.sql';
        $dbName = trim($conf['path'], '/');
        $cmd = sprintf('mysqldump -h %s --port %s -u %s --password=%s %s --ignore-table=%s.user > %s',
            $conf['host'],
            $conf['port'],
            $conf['user'],
            $conf['pass'],
            $dbName,
            $dbName,
            $sqlFile
        );
        $output = [];
        $exit_status = null;
        exec($cmd, $output, $exit_status);
        return $sqlFile;
    }

    private function dumpAttachments(): string
    {
        $tmpDir = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'tmp';
        $attachmentsDir = implode(DIRECTORY_SEPARATOR, [
            $this->getParameter('kernel.project_dir'),
            'public',
            'uploads',
            'attachments'
        ]);
        $finder = new Finder();
        $finder->files()->in($attachmentsDir);
        $zipFilePath = 'faw-attachments-' . date('Y-m-d-H-i-s') . '.zip';
        $zipFileName = implode(DIRECTORY_SEPARATOR, [
            $this->getParameter('kernel.project_dir'),
            'public',
            'data',
            $zipFilePath
        ]);
        $z = new ZipArchive();
        $outZipPath = tempnam($tmpDir, '');
        $z->open($outZipPath, ZIPARCHIVE::CREATE);
        foreach ($finder as $file) {
            $z->addFile($file->getRealPath(), $file->getRelativePathname());
        }
        $z->close();
        rename($outZipPath, $zipFileName);
        return $zipFilePath;
    }

    /**
     * @Route("/get/dump/{name}/{key}", name="get_dump")
     */
    public function getDump($name, $key)
    {
        $secret = $this->getParameter('app.dump_key');
        if ($key === $secret) {
            if ($name === 'db') {
                $file = $this->dumpDB();
                $info = pathinfo($file);
                $responseFile = implode(DIRECTORY_SEPARATOR, [
                    $this->getParameter('kernel.project_dir'),
                    'public',
                    'data',
                    $info['basename']
                ]);
                rename($file, $responseFile);
            } elseif ($name === 'attachment') {
                $responseFile = implode(DIRECTORY_SEPARATOR, [
                    $this->getParameter('kernel.project_dir'),
                    'public',
                    'data',
                    $this->dumpAttachments()
                ]);
            } else {
                return new Response();
            }
            $response = new BinaryFileResponse($responseFile);
            $response->headers->set('Content-Type', 'text/plain');

            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', pathinfo($responseFile)['basename']));
            $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
            return $response;
        } else {
            return new Response();
        }
    }

    /**
     * @Route("/admin/download/file", name="admin_download_file")
     */
    public function downloadFile(Request $request)
    {
        if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $fileName = $request->query->get('f');
            if ($fileName) {
                $info = pathinfo($fileName);
                if (key_exists('dirname', $info)) {
                    $tmpDir = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'backups';
                    if ($info['dirname'] === $tmpDir) {
                        $data = file_get_contents($fileName);
                        $response = new Response($data);
                        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $info['basename']));
                        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
                        return $response;
                    }
                }
            }
        }
        return new BinaryFileResponse(null);
    }
}
