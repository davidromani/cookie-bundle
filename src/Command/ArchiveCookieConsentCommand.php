<?php

namespace Eveltic\CookieBundle\Command;

use ReflectionClass;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Eveltic\CookieBundle\Entity\UserCookieConsent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Command to archive and delete old cookie consent records.
 * 
 * This command allows the user to archive records older than a specified date and save them in various formats (CSV, HTML, or log).
 * It also supports a "dry run" mode to preview the records that would be archived without actually modifying any data.
 */
#[AsCommand(
    name: 'ev:cookie:archive',
    description: 'Archive and delete old cookie consent records.'
)]
class ArchiveCookieConsentCommand extends Command
{
     /**
     * Constructor for the ArchiveCookieConsentCommand class.
     *
     * @param EntityManagerInterface $entityManager 
     *        The Doctrine Entity Manager used to interact with the database.
     *
     * @param KernelInterface $kernel 
     *        The Symfony Kernel Interface used to get the project's root directory.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly KernelInterface $kernel
    ) {
        parent::__construct();
    }

    /**
     * Configures the command options and arguments.
     */
    protected function configure(): void
    {
        $this
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date to archive records before (format: Y-m-d)')
            ->addOption('output-format', null, InputOption::VALUE_OPTIONAL, 'Output format: csv or html')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Perform a dry run without modifying any data');
    }

    /**
     * Executes the command to archive and delete old cookie consent records.
     *
     * @param InputInterface $input 
     *        The input interface used to access command options and arguments.
     *
     * @param OutputInterface $output 
     *        The output interface used to display messages to the console.
     *
     * @return int 
     *        Returns Command::SUCCESS if the command executed successfully, otherwise Command::FAILURE.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dateOption = $input->getOption('date');
        $dryRun = $input->getOption('dry-run');
        $outputFormat = $input->getOption('output-format');

        $date = $this->getDate($dateOption, $io);
        $records = $this->fetchRecords($date);

        if (empty($records)) {
            $io->success('No records found to archive.');
            return Command::SUCCESS;
        }

        [$oldestDate, $newestDate] = $this->getDateRange($records);
        
        if ($dryRun) {
            $io->info("Dry run: The following records would be archived (from {$oldestDate} to {$newestDate})");
            $this->displayRecords($records, $io);
        } else {
            if (!$outputFormat) {
                $outputFormat = $this->askForOutputFormat($io);
            }
            $logFilePath = $this->getLogFilePath($oldestDate, $newestDate, $outputFormat);

            $this->createLogDirectory($logFilePath);
            $io->info("Archiving records from {$oldestDate} to {$newestDate} to {$logFilePath}");

            if ($outputFormat === 'html') {
                $this->writeRecordsToHtmlFile($records, $logFilePath, $io);
            } else if ($outputFormat === 'csv') {
                $this->writeRecordsToCsvFile($records, $logFilePath, $io);
            } else if ($outputFormat === 'log') {
                $this->writeRecordsToLogFile($records, $logFilePath, $io);
            } else {
                $io->error('Output format not supported, the supported ones are "log", "csv", "html".');
                return Command::FAILURE;
            }
            $this->deleteArchivedRecords($records, $io);

            $io->success('Records archived and deleted successfully.');
        }

        return Command::SUCCESS;
    }

    /**
     * Determines the date to archive records before.
     * If no date is provided, it asks the user to select one.
     *
     * @param ?string $dateOption 
     *        An optional date string in the format 'Y-m-d' indicating the cutoff date for archiving records.
     *
     * @param SymfonyStyle $io 
     *        The SymfonyStyle interface used to interact with the user in the console.
     *
     * @return \DateTime 
     *         A DateTime object representing the cutoff date.
     */
    private function getDate(?string $dateOption, SymfonyStyle $io): \DateTime
    {
        if (!$dateOption) {
            $dateOption = $this->askForDate($io);
        }
        return new \DateTime($dateOption);
    }

    /**
     * Calculates the date range (oldest and newest dates) from the records.
     *
     * @param array $records 
     *        An array of records that are being archived.
     *
     * @return array 
     *         An array with two elements: the oldest date and the newest date, both in the format 'Y-m-d'.
     */
    private function getDateRange(array $records): array
    {
        $dates = array_map(fn($record) => $record->getConsentDate()->format('Y-m-d'), $records);
        $oldestDate = min($dates);
        $newestDate = max($dates);

        return [$oldestDate, $newestDate];
    }

    /**
     * Returns the path to the directory where log files will be stored.
     *
     * @return string 
     *         The path to the log directory.
     */
    private function getLogPath(): string
    {
        return $this->kernel->getProjectDir() . '/var/cookie-consent/';
    }

    /**
     * Constructs the full path to the log file, including the file name based on the date range and format.
     *
     * @param string $oldestDate 
     *        The oldest date in the records, used in the file name.
     *
     * @param string $newestDate 
     *        The newest date in the records, used in the file name.
     *
     * @param string $format 
     *        The output format for the log file (e.g., 'csv', 'html', 'log').
     *
     * @return string 
     *         The full path to the log file, including the file name.
     */
    private function getLogFilePath(string $oldestDate, string $newestDate, string $format): string
    {
        $currentDate = (new \DateTime())->format('Ymd_His');
        
        $extension = match ($format) {
            'html' => 'html',
            'log'  => 'log',
            'csv'  => 'csv',
            default => $format,
        };
        
        $logFileName = "{$oldestDate}-{$newestDate}_exported_{$currentDate}.{$extension}";
        return $this->getLogPath() . $logFileName;
    }

    /**
     * Creates the log directory if it does not already exist.
     *
     * @param string $logFilePath 
     *        The full path to the log file, used to determine the directory.
     *
     * @return void
     */
    private function createLogDirectory(string $logFilePath): void
    {
        $logDir = dirname($logFilePath);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Fetches the records that need to be archived based on the specified date.
     *
     * @param \DateTime $date 
     *        The cutoff date; records older than this date will be archived.
     *
     * @return array 
     *         An array of records that need to be archived.
     */
    private function fetchRecords(\DateTime $date): array
    {
        $repository = $this->entityManager->getRepository(UserCookieConsent::class);

        $queryBuilder = $repository->createQueryBuilder('ucc')
            ->where('ucc.consentDate < :date')
            ->setParameter('date', $date)
            ->getQuery();

        return $queryBuilder->getResult();
    }

    /**
     * Writes the archived records to a log file in plain text format.
     *
     * @param array $records 
     *        The records to be written to the log file.
     *
     * @param string $logFilePath 
     *        The path to the log file where the records will be written.
     *
     * @param SymfonyStyle $io 
     *        The SymfonyStyle interface used to display progress and success messages.
     *
     * @return void
     */
    private function writeRecordsToLogFile(array $records, string $logFilePath, SymfonyStyle $io): void
    {
        if (empty($records)) {
            return;
        }
    
        $logFile = fopen($logFilePath, 'w');
        $progressBar = new ProgressBar($io, count($records));
        $progressBar->start();
    
        $reflection = new ReflectionClass(UserCookieConsent::class);
        $properties = $reflection->getProperties();
        $headers = array_map(fn($prop) => $prop->getName(), $properties);
    
        fwrite($logFile, implode(' | ', $headers) . "\n");
    
        foreach ($records as $record) {
            $row = [];
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($record);
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_object($value)) {
                    if (method_exists($value, '__toString')) {
                        $value = (string) $value;
                    } else {
                        $value = json_encode($value);
                    }
                }
                $row[] = $value;
            }
            fwrite($logFile, implode(' | ', $row) . "\n");
            $progressBar->advance();
        }
    
        fclose($logFile);
        $progressBar->finish();
    
        $io->newLine(2);
        $io->success(sprintf('Records successfully written to log file in %s folder.', $this->getLogPath()));
    }
    
    /**
     * Writes the archived records to a CSV file.
     *
     * @param array $records 
     *        The records to be written to the CSV file.
     *
     * @param string $logFilePath 
     *        The path to the CSV file where the records will be written.
     *
     * @param SymfonyStyle $io 
     *        The SymfonyStyle interface used to display progress and success messages.
     *
     * @return void
     */
    private function writeRecordsToCsvFile(array $records, string $logFilePath, SymfonyStyle $io): void
    {
        if (empty($records)) {
            return;
        }

        $logFile = fopen($logFilePath, 'w');
        $progressBar = new ProgressBar($io, count($records));
        $progressBar->start();

        $reflection = new ReflectionClass(UserCookieConsent::class);
        $properties = $reflection->getProperties();
        $headers = array_map(fn($prop) => $prop->getName(), $properties);
        
        fputcsv($logFile, $headers);

        foreach ($records as $record) {
            $row = [];
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($record);
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_object($value)) {
                    if (method_exists($value, '__toString')) {
                        $value = (string) $value;
                    } else {
                        $value = json_encode($value);
                    }
                }
                $row[] = $value;
            }
            fputcsv($logFile, $row);
            $progressBar->advance();
        }

        fclose($logFile);
        $progressBar->finish();

        $io->newLine(2);
        $io->success(sprintf('Records successfully written to CSV file in %s folder.', $this->getLogPath()));
    }

    /**
     * Writes the archived records to an HTML file formatted as a table.
     *
     * @param array $records 
     *        The records to be written to the HTML file.
     *
     * @param string $logFilePath 
     *        The path to the HTML file where the records will be written.
     *
     * @param SymfonyStyle $io 
     *        The SymfonyStyle interface used to display progress and success messages.
     *
     * @return void
     */
    private function writeRecordsToHtmlFile(array $records, string $logFilePath, SymfonyStyle $io): void
    {
        if (empty($records)) {
            return;
        }

        $htmlFile = fopen($logFilePath, 'w');
        $progressBar = new ProgressBar($io, count($records));
        $progressBar->start();

        $reflection = new ReflectionClass(UserCookieConsent::class);
        $properties = $reflection->getProperties();
        $headers = array_map(fn($prop) => $prop->getName(), $properties);
        
        fwrite($htmlFile, "<html><body><table border='1'>\n<tr>");
        foreach ($headers as $header) {
            fwrite($htmlFile, "<th>{$header}</th>");
        }
        fwrite($htmlFile, "</tr>\n");

        foreach ($records as $record) {
            fwrite($htmlFile, "<tr>");
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($record);
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_object($value)) {
                    if (method_exists($value, '__toString')) {
                        $value = (string) $value;
                    } else {
                        $value = json_encode($value);
                    }
                }
                fwrite($htmlFile, "<td>{$value}</td>");
            }
            fwrite($htmlFile, "</tr>\n");
            $progressBar->advance();
        }

        fwrite($htmlFile, "</table></body></html>");
        fclose($htmlFile);
        $progressBar->finish();

        $io->newLine(2);
        $io->success(sprintf('Records successfully written to HTML file in %s folder.', $this->getLogPath()));
    }

    /**
     * Deletes the records that have been archived from the database.
     *
     * @param array $records 
     *        The records to be deleted.
     *
     * @param SymfonyStyle $io 
     *        The SymfonyStyle interface used to display success messages.
     *
     * @return void
     */
    private function deleteArchivedRecords(array $records, SymfonyStyle $io): void
    {
        foreach ($records as $record) {
            $this->entityManager->remove($record);
        }

        $this->entityManager->flush();

        $io->success('Archived records successfully deleted from the database.');
    }

    /**
     * Displays the records in a table format in the console.
     *
     * @param array $records 
     *        The records to be displayed.
     *
     * @param SymfonyStyle $io 
     *        The SymfonyStyle interface used to display the table.
     *
     * @return void
     */
    private function displayRecords(array $records, SymfonyStyle $io): void
    {
        if (empty($records)) {
            $io->warning('No records to display.');
            return;
        }

        $reflection = new ReflectionClass(UserCookieConsent::class);
        $properties = $reflection->getProperties();
        $headers = array_map(fn($prop) => $prop->getName(), $properties);

        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($record);
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_object($value)) {
                    if (method_exists($value, '__toString')) {
                        $value = (string) $value;
                    } else {
                        $value = json_encode($value);
                    }
                }
                $row[] = $value;
            }
            $rows[] = $row;
        }

        $io->table($headers, $rows);
    }

    /**
     * Asks the user for a date range to filter the records to be archived.
     *
     * @param SymfonyStyle $io 
     *        The SymfonyStyle interface used to interact with the user in the console.
     *
     * @return string 
     *         The selected date range as a string in the format 'Y-m-d'.
     */
    private function askForDate(SymfonyStyle $io): string
    {
        $choices = [
            'Today',
            '1 week ago',
            '1 month ago',
            '6 months ago',
            '12 months ago',
            '24 months ago',
        ];

        $question = new ChoiceQuestion('Please select a date range', $choices, 1);
        $selectedChoice = $io->askQuestion($question);

        return match ($selectedChoice) {
            'Today' => (new \DateTime('+1 day'))->format('Y-m-d'),
            '1 week ago' => (new \DateTime('-1 week'))->format('Y-m-d'),
            '1 month ago' => (new \DateTime('-1 month'))->format('Y-m-d'),
            '6 months ago' => (new \DateTime('-6 months'))->format('Y-m-d'),
            '12 months ago' => (new \DateTime('-12 months'))->format('Y-m-d'),
            '24 months ago' => (new \DateTime('-24 months'))->format('Y-m-d'),
            default => throw new \RuntimeException('Invalid date choice.'),
        };
    }
    
    /**
     * Asks the user to select the output format for the archived records.
     *
     * @param SymfonyStyle $io 
     *        The SymfonyStyle interface used to interact with the user in the console.
     *
     * @return string 
     *         The selected output format ('log', 'csv', 'html').
     */
    private function askForOutputFormat(SymfonyStyle $io): string
    {
        $choices = ['log', 'csv', 'html'];

        $question = new ChoiceQuestion('Please select an output format', $choices, 0);
        return $io->askQuestion($question);
    }
}