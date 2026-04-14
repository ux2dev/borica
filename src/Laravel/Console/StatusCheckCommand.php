<?php
declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Exception\BoricaException;
use Ux2Dev\Borica\Laravel\BoricaManager;

class StatusCheckCommand extends Command
{
    protected $signature = 'borica:status-check
        {order : The 6-digit order number}
        {--type= : Transaction type: purchase, pre-auth, reversal, pre-auth-complete, pre-auth-reversal}
        {--merchant= : Merchant config name (default from config)}';

    protected $description = 'Check the status of a BORICA transaction';

    private const TYPE_MAP = [
        'purchase' => TransactionType::Purchase,
        'pre-auth' => TransactionType::PreAuth,
        'pre-auth-complete' => TransactionType::PreAuthComplete,
        'pre-auth-reversal' => TransactionType::PreAuthReversal,
        'reversal' => TransactionType::Reversal,
    ];

    public function handle(BoricaManager $manager): int
    {
        $type = $this->option('type');

        if ($type === null) {
            $this->error('The --type option is required. Valid types: ' . implode(', ', array_keys(self::TYPE_MAP)));
            return self::FAILURE;
        }

        $transactionType = self::TYPE_MAP[$type] ?? null;

        if ($transactionType === null) {
            $this->error('Invalid transaction type. Valid types: ' . implode(', ', array_keys(self::TYPE_MAP)));
            return self::FAILURE;
        }

        $merchantName = $this->option('merchant');
        $borica = $merchantName ? $manager->merchant($merchantName) : $manager->merchant();

        $order = $this->argument('order');

        try {
            $request = $borica->createStatusCheckRequest(
                order: $order,
                transactionType: $transactionType,
            );
        } catch (BoricaException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $gatewayUrl = $borica->getGatewayUrl();

        $this->info("Sending status check to {$gatewayUrl}...");

        $httpResponse = Http::asForm()->post($gatewayUrl, $request->toArray());

        if (!$httpResponse->successful()) {
            $this->error("HTTP error: {$httpResponse->status()}");
            return self::FAILURE;
        }

        parse_str($httpResponse->body(), $responseData);

        try {
            $response = $borica->parseResponse($responseData, $transactionType);
        } catch (BoricaException $e) {
            $this->error("Parse error: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->displayResponse($response);

        return self::SUCCESS;
    }

    private function displayResponse(\Ux2Dev\Borica\Response\Response $response): void
    {
        $status = $response->isSuccessful() ? 'SUCCESS' : 'FAILED';
        $action = $response->getAction();
        $rc = $response->getRc();

        $this->newLine();

        if ($response->isSuccessful()) {
            $this->info("Status: {$status} (ACTION={$action}, RC={$rc})");
        } else {
            $this->warn("Status: {$status} (ACTION={$action}, RC={$rc})");
            $error = $response->getErrorMessage();
            if ($error !== '') {
                $this->warn("Error: {$error}");
            }
        }

        $this->table(
            ['Field', 'Value'],
            array_filter([
                ['Order', $response->getOrder()],
                ['Amount', $response->getAmount()],
                ['Currency', $response->getCurrency()],
                ['RRN', $response->getRrn()],
                ['INT_REF', $response->getIntRef()],
                ['Card', $response->getCard()],
                ['Approval', $response->getApproval()],
            ], fn ($row) => $row[1] !== null),
        );
    }
}
