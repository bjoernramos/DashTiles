
// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
chdir(FCPATH);

// Composer autoload
if (! is_file(FCPATH . '../vendor/autoload.php')) {
    http_response_code(503);
    echo 'Composer autoload not found. Run composer install.';
    exit(1);
}
require FCPATH . '../vendor/autoload.php';

// Load the paths config to locate the framework and app directories.
$pathsPath = FCPATH . '../app/Config/Paths.php';
if (! is_file($pathsPath)) {
    http_response_code(503);
    echo 'App paths configuration missing.';
    exit(1);
}
require $pathsPath;

$paths = new Config\Paths();

// Define path to the system directory
define('SYSTEMPATH', rtrim($paths->systemDirectory, '\\/') . DIRECTORY_SEPARATOR);
define('APPPATH', rtrim($paths->appDirectory, '\\/') . DIRECTORY_SEPARATOR);
define('WRITEPATH', rtrim($paths->writableDirectory, '\\/') . DIRECTORY_SEPARATOR);
define('ROOTPATH', rtrim($paths->rootDirectory, '\\/') . DIRECTORY_SEPARATOR);
define('PUBLICPATH', FCPATH);

// Bootstrapping the framework
require_once SYSTEMPATH . 'bootstrap.php';

// Run the application
\CodeIgniter\CodeIgniter::run();

