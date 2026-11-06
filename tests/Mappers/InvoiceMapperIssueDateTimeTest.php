<?php

namespace Saleh7\Zatca\Tests\Mappers;

use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\Mappers\InvoiceMapper;
use Saleh7\Zatca\Tests\Fixtures\InvoiceDataFixture;

class InvoiceMapperIssueDateTimeTest extends TestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function issueDateAndTime(array $data): array
    {
        $xml = GeneratorInvoice::invoice((new InvoiceMapper)->mapToInvoice($data))->getXML();
        preg_match('#<cbc:IssueDate>(.*?)</cbc:IssueDate>\s*<cbc:IssueTime>(.*?)</cbc:IssueTime>#s', $xml, $matches);

        return [$matches[1], $matches[2]];
    }

    public function test_date_only_issue_date_is_not_shifted_in_non_utc_timezone(): void
    {
        date_default_timezone_set('Asia/Riyadh');
        $data = InvoiceDataFixture::simplified();
        $data['issueDate'] = '2026-01-15';
        $data['issueTime'] = '10:00:00';

        $this->assertSame(['2026-01-15', '07:00:00Z'], $this->issueDateAndTime($data));
    }

    public function test_issue_date_and_time_describe_the_same_instant_after_utc_conversion(): void
    {
        date_default_timezone_set('Asia/Riyadh');
        $data = InvoiceDataFixture::simplified();
        $data['issueDate'] = '2026-01-15';
        $data['issueTime'] = '01:30:00'; // 2026-01-14 22:30 UTC

        $this->assertSame(['2026-01-14', '22:30:00Z'], $this->issueDateAndTime($data));
    }

    public function test_explicit_offset_in_issue_time_is_respected(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['issueDate'] = '2026-01-15';
        $data['issueTime'] = '2026-01-15T10:00:00+03:00';

        $this->assertSame(['2026-01-15', '07:00:00Z'], $this->issueDateAndTime($data));
    }

    public function test_utc_default_timezone_output_is_unchanged(): void
    {
        date_default_timezone_set('UTC');
        $data = InvoiceDataFixture::simplified();
        $data['issueDate'] = '2024-09-07 17:41:08';
        $data['issueTime'] = '2024-09-07 17:41:08';

        $this->assertSame(['2024-09-07', '17:41:08Z'], $this->issueDateAndTime($data));
    }

    public function test_unparsable_issue_date_keeps_falling_back_to_today(): void
    {
        date_default_timezone_set('UTC');
        $data = InvoiceDataFixture::simplified();
        $data['issueDate'] = '2026-13-45';
        $data['issueTime'] = '10:00:00';

        [$date] = $this->issueDateAndTime($data);

        $this->assertSame(gmdate('Y-m-d'), $date);
    }
}
