<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Console;

use Illuminate\Console\Command;
use Ux2Dev\Borica\Certificate\CertificateInspector;
use Ux2Dev\Borica\Exception\ConfigurationException;

class CheckCertificatesCommand extends Command
{
    protected $signature = 'borica:certificate:check
        {--path= : Check a specific certificate file (skip config discovery)}
        {--warning-days=30 : Warn when a cert expires within this many days}';

    protected $description = 'Report expiry status for all configured BORICA certificates';

    public function handle(): int
    {
        $warningDays = (int) $this->option('warning-days');
        $rows = [];
        $hasWarning = false;
        $hasError = false;

        if ($this->option('path')) {
            $rows[] = $this->inspect('(explicit)', $this->option('path'), $warningDays, $hasWarning, $hasError);
        } else {
            foreach (config('borica.cgi.merchants', []) as $name => $cfg) {
                if (!empty($cfg['certificate'])) {
                    $rows[] = $this->inspect("cgi.{$name}", $cfg['certificate'], $warningDays, $hasWarning, $hasError);
                }
            }
            foreach (config('borica.checkout.merchants', []) as $name => $cfg) {
                if (!empty($cfg['certificate'])) {
                    $rows[] = $this->inspect("checkout.{$name}", $cfg['certificate'], $warningDays, $hasWarning, $hasError);
                }
            }
        }

        if ($rows === []) {
            $this->warn('No certificates configured. Set `certificate` on merchants in config/borica.php, or use --path.');
            return self::SUCCESS;
        }

        $this->table(['Merchant', 'Subject', 'Not After', 'Days Left', 'Status'], $rows);

        if ($hasError) return self::FAILURE;
        if ($hasWarning) return 2;
        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function inspect(string $label, string $path, int $warningDays, bool &$hasWarning, bool &$hasError): array
    {
        try {
            $info = CertificateInspector::fromFile($path);
        } catch (ConfigurationException $e) {
            $hasError = true;
            return [$label, $e->getMessage(), '-', '-', 'ERROR'];
        }

        $days = $info->daysUntilExpiry();
        $status = 'OK';
        if ($info->isExpired()) { $status = 'EXPIRED'; $hasError = true; }
        elseif ($info->isExpiringSoon($warningDays)) { $status = "WARNING (< {$warningDays}d)"; $hasWarning = true; }

        return [
            $label,
            $info->subject,
            $info->notAfter->format('Y-m-d'),
            (string) $days,
            $status,
        ];
    }
}
