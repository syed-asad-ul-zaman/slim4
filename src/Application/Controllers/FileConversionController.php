<?php

namespace App\Application\Controllers;

use PhpOffice\PhpWord\IOFactory;
use Psr\Log\LoggerInterface;
use Spatie\PdfToImage\Pdf;
use Imagick;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PhpOffice\PhpWord\Settings;
use Dompdf\Dompdf;

class FileConversionController
{
    private $log;
    private $uploadsDir;
    private $convertedDir;

    public function __construct(LoggerInterface $logger, ContainerInterface $container) {
        $this->log = $logger;

        $settings = $container->get('settings');
        $this->uploadsDir = $settings['paths']['uploaded_files_path'];
        $this->convertedDir = $settings['paths']['converted_files_path'];
    }

    public function convertFile(Request $req, Response $res, $args)
    {
        $uploadedFiles = $req->getUploadedFiles();

        if (empty($uploadedFiles['file'])) {
            $res->getBody()->write(json_encode(['error' => 'No file uploaded']));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $file = $uploadedFiles['file'];
        $fileType = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        $originalFilePath = $this->uploadsDir . '/' . $file->getClientFilename();
        
        $file->moveTo($originalFilePath);

        try {
            $convertedFilePath = $this->handleFileConversion($originalFilePath, $fileType);

            $fileUrl = sprintf(
                '%s://%s/files/%s',
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http', 
                $_SERVER['HTTP_HOST'], 
                basename($convertedFilePath)
            );
    
            $responseData = [
                'message' => 'File converted successfully',
                'file_link' => $fileUrl
            ];

            $res->getBody()->write(json_encode($responseData));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\Exception $e) {
            $res->getBody()->write(json_encode(['error' => 'File conversion failed: ' . $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function handleFileConversion($originalFilePath, $fileType)
    {
        switch ($fileType) {
            case 'doc':
            case 'docx':
                return $this->convertWordToPDF($originalFilePath);
            case 'xls':
            case 'xlsx':
                return $this->convertExcelToPDF($originalFilePath);
            case 'ppt':
            case 'pptx':
                return $this->convertPPTToPDF($originalFilePath);
            case 'pdf':
                return $this->convertPDFToJPG($originalFilePath);
            case 'heic':
                return $this->convertHEICToJPG($originalFilePath);
            default:
                throw new \Exception('Unsupported file type');
        }
    }

    private function convertWordToPDF($originalFilePath)
    {
        $dompdfPath = realpath(__DIR__ . '/../../../vendor/dompdf/dompdf');
        if (!$dompdfPath || !is_dir($dompdfPath)) {
            throw new \Exception('Dompdf library path not found: ' . $dompdfPath);
        }
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath($dompdfPath);
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($originalFilePath);
        $convertedPath = $this->convertedDir . '/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.pdf';
        $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
        $pdfWriter->save($convertedPath);
        return $convertedPath;
    }

    private function convertExcelToPDF($originalFilePath)
    {
        $convertedPath = $this->convertedDir . '/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.pdf';
        exec("soffice --headless --convert-to pdf --outdir " . escapeshellarg(dirname($convertedPath)) . " " . escapeshellarg($originalFilePath));
        return $convertedPath;
    }

    private function convertPPTToPDF($originalFilePath)
    {
        $convertedPath = $this->convertedDir . '/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.pdf';
        exec("soffice --headless --convert-to pdf --outdir " . escapeshellarg(dirname($convertedPath)) . " " . escapeshellarg($originalFilePath));
        return $convertedPath;
    }

    private function convertPDFToJPG($originalFilePath)
    {
        $convertedPath = $this->convertedDir . '/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.jpg';
        $pdf = new Pdf($originalFilePath);
        $pdf->setOutputFormat('jpg')->saveImage($convertedPath);
        return $convertedPath;
    }

    private function convertHEICToJPG($originalFilePath)
    {
        $convertedPath = $this->convertedDir . '/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.jpg';
        $imagick = new Imagick($originalFilePath);
        $imagick->setImageFormat('jpg');
        $imagick->writeImage($convertedPath);
        return $convertedPath;
    }
}
