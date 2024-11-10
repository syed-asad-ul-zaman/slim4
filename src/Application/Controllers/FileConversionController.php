<?php

namespace App\Application\Controllers;

use PhpOffice\PhpWord\IOFactory;
use Psr\Log\LoggerInterface;
use Spatie\PdfToImage\Pdf;
use Imagick;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class FileConversionController
{
    private $log;
    private $uploadsDir;

    public function __construct(LoggerInterface $logger, ContainerInterface $container) {
        $this->log = $logger;

        $settings = $container->get('settings');
        $this->uploadsDir = $settings['paths']['uploads'];
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
        $originalFilePath = $this->uploadsDir . '/uploaded_files/' . $file->getClientFilename();
        
        $file->moveTo($originalFilePath);

        try {
            $convertedFilePath = $this->handleFileConversion($originalFilePath, $fileType);

            $fileUrl = str_replace(
                [$_SERVER['DOCUMENT_ROOT'], '\\'],
                ['http://localhost', '/'],
                realpath($convertedFilePath)
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
        $phpWord = IOFactory::load($originalFilePath);
        $outputPath = $this->uploadsDir . '/files/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.pdf';
        $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
        $pdfWriter->save($outputPath);
        return $outputPath;
    }

    private function convertExcelToPDF($originalFilePath)
    {
        $outputPath = $this->uploadsDir . '/files/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.pdf';
        exec("soffice --headless --convert-to pdf --outdir " . escapeshellarg(dirname($outputPath)) . " " . escapeshellarg($originalFilePath));
        return $outputPath;
    }

    private function convertPPTToPDF($originalFilePath)
    {
        $outputPath = $this->uploadsDir . '/files/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.pdf';
        exec("soffice --headless --convert-to pdf --outdir " . escapeshellarg(dirname($outputPath)) . " " . escapeshellarg($originalFilePath));
        return $outputPath;
    }

    private function convertPDFToJPG($originalFilePath)
    {
        $outputPath = $this->uploadsDir . '/files/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.jpg';
        $pdf = new Pdf($originalFilePath);
        $pdf->setOutputFormat('jpg')->saveImage($outputPath);
        return $outputPath;
    }

    private function convertHEICToJPG($originalFilePath)
    {
        $outputPath = $this->uploadsDir . '/files/' . pathinfo($originalFilePath, PATHINFO_FILENAME) . '.jpg';
        $imagick = new Imagick($originalFilePath);
        $imagick->setImageFormat('jpg');
        $imagick->writeImage($outputPath);
        return $outputPath;
    }
}
