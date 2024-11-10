# Slim 4 File Conversion API

This project provides an API built with Slim 4 for file conversions, including converting Word, Excel, and PPT files to PDF, PDF to JPG, and HEIC to JPG.

## Installation

Follow the steps below to set up the project and required dependencies.

### Prerequisites

1. **PHP 8.3 or higher** with Composer
2. **Windows OS** (the instructions are tailored for Windows with Laragon)
3. **ImageMagick**
4. **LibreOffice**
5. **Imagick PHP extension**

### Steps

#### 1. Clone the Repository

```bash
git clone https://github.com/your-username/your-repository.git
cd your-repository
composer install
```

#### 2. Install ImageMagick

ImageMagick is required for converting HEIC images to JPG.

- **Download Link**: [ImageMagick for Windows](https://imagemagick.org/script/download.php#windows)
- **Installation**:
  - Download the appropriate installer for your system (choose the **DLL** version compatible with PHP 8.3).
  - Run the installer and select the option to install legacy tools (e.g., `convert.exe`).
  - After installation, add ImageMagick to your system’s PATH:
    1. Right-click **This PC** > **Properties**.
    2. Go to **Advanced system settings** > **Environment Variables**.
    3. Find the **Path** variable, click **Edit**, and add the ImageMagick installation path (e.g., `C:\Program Files\ImageMagick-7.0.10-Q16`).

#### 3. Install LibreOffice

LibreOffice is used to convert Excel and PowerPoint files to PDF.

- **Download Link**: [LibreOffice for Windows](https://www.libreoffice.org/download/download/)
- **Installation**:
  - Download and install LibreOffice.
  - Add LibreOffice to your system’s PATH:
    1. Right-click **This PC** > **Properties**.
    2. Go to **Advanced system settings** > **Environment Variables**.
    3. Find the **Path** variable, click **Edit**, and add the LibreOffice program path (e.g., `C:\Program Files\LibreOffice\program`).

#### 4. Install Imagick PHP Extension

The Imagick PHP extension is required to use ImageMagick within PHP.

1. **Download the DLL**:
   - Visit the [PECL repository for Imagick](https://pecl.php.net/package/imagick) and download the version that matches your PHP setup:
     - Choose **PHP 8.3**.
     - Choose **Thread Safe (TS)** or **Non Thread Safe (NTS)** based on your PHP installation (use `phpinfo()` to check).
     - Ensure you select **x64** if your PHP is 64-bit.
2. **Install the Extension**:
   - Extract the downloaded `.zip` file and locate the `php_imagick.dll` file.
   - Place `php_imagick.dll` in your PHP extension directory (e.g., `laragon\bin\php\php-8.3.12\ext`).
3. **Enable the Extension**:
   - Open your `php.ini` file (located in `laragon\bin\php\php-8.3.12\php.ini`).
   - Add the following line to enable Imagick:
     ```ini
     extension=php_imagick.dll
     ```
   - Save the file and restart Laragon.

#### 5. Update Laragon Configuration to Serve the `/public` Directory

To serve the project from the `/public` directory, configure Laragon as follows:

1. Open Laragon and go to **Menu** > **Apache** > **sites-enabled** > open the site configuration file (or create a new one).
2. Update the document root to point to the `public` folder:
   ```apache
   <VirtualHost *:80>
       DocumentRoot "E:/laragon/www/your-repository/public"
       ServerName your-domain.test
       <Directory "E:/laragon/www/your-repository/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. Edit your `hosts` file to map `your-domain.test` to `localhost`:
   - Open `C:\Windows\System32\drivers\etc\hosts` as Administrator.
   - Add this line:
     ```plaintext
     127.0.0.1 your-domain.test
     ```
4. Restart Laragon and visit `http://your-domain.test` to access the project.

#### 6. Set Up Project Directories and Permissions

1. **Create directories** for file uploads:
   ```bash
   mkdir -p storage/uploads/uploaded_files
   mkdir -p storage/uploads/files
   ```
2. **Set permissions** to ensure PHP can write to these directories. Right-click each directory, go to **Properties** > **Security**, and enable **Write** permissions for the **Users** group.

#### 7. Environment Configuration

Copy `.env.example` to `.env` and configure any environment variables as needed.

```bash
cp .env.example .env
```

#### 8. Run the Application

After completing the setup, start your application with Laragon and test the API endpoints.

---

## Usage

You can use a tool like `curl` or Postman to test file uploads. Example request:

```bash
curl --location 'http://your-domain.test/api/convert' \
--header 'Content-Type: multipart/form-data' \
--form 'file=@"/path/to/your/file.docx"'
```

## Troubleshooting

- **"Upload target path is not writable"**: Ensure `storage/uploads/uploaded_files` and `storage/uploads/files` are writable by PHP.
- **Imagick Not Loaded**: Verify that `php_imagick.dll` is in the PHP `ext` directory and that `extension=php_imagick.dll` is in `php.ini`.

## License

This project is licensed under the MIT License.
