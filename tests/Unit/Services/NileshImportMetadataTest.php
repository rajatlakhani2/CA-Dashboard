<?php

namespace Tests\Unit\Services;

use App\Services\ImportClientsNileshMetadata;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NileshImportMetadataTest extends TestCase
{
    use RefreshDatabase;

    private ImportClientsNileshMetadata $metadata;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metadata = new ImportClientsNileshMetadata;
        $this->tempDir = sys_get_temp_dir().'/nilesh-test-'.uniqid();
        File::makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    public function test_should_skip_system_folders(): void
    {
        $this->assertTrue($this->metadata->shouldSkipFolder('.hidden'));
        $this->assertTrue($this->metadata->shouldSkipFolder('desktop.ini'));
        $this->assertTrue($this->metadata->shouldSkipFolder('Extra'));
        $this->assertTrue($this->metadata->shouldSkipFolder('Payment sheet'));
        $this->assertFalse($this->metadata->shouldSkipFolder('Rajesh Shah'));
    }

    public function test_extract_gst_metadata_detects_gst_folder_and_gstin(): void
    {
        $clientDir = $this->tempDir.'/Mitesh Agia- GST';
        $gstDir = $clientDir.'/GST';
        $returnDir = $clientDir.'/GST return';
        File::makeDirectory($gstDir, 0755, true);
        File::makeDirectory($returnDir, 0755, true);
        File::put($gstDir.'/27ABCDE1234F1Z5_registration.pdf', 'pdf');
        File::put($returnDir.'/GSTR3B_Apr.pdf', 'pdf');

        $meta = $this->metadata->extractGstMetadata($clientDir);

        $this->assertTrue($meta['has_gst']);
        $this->assertSame('27ABCDE1234F1Z5', $meta['gstin']);
        $this->assertNotEmpty($meta['gst_files']);
    }

    public function test_masked_pan_with_x_is_rejected(): void
    {
        $this->assertTrue($this->metadata->isMaskedPan('XXXXX1234A'));
        $this->assertTrue($this->metadata->isMaskedPan('ABCPX1234F'));
        $this->assertFalse($this->metadata->isMaskedPan('ABCPK1234L'));
    }

    public function test_extract_pan_from_pdf_text_content(): void
    {
        $pdfPath = $this->tempDir.'/ack.pdf';
        $body = '%PDF-1.4 sample PAN ABCPK1234L in acknowledgement';
        File::put($pdfPath, $body);

        $pan = $this->metadata->extractPanFromPdfContents($pdfPath);

        $this->assertSame('ABCPK1234L', $pan);
    }

    public function test_resolve_pan_skips_masked_filename_and_reads_pdf(): void
    {
        $clientDir = $this->tempDir.'/Masked Client';
        File::makeDirectory($clientDir, 0755, true);
        File::put($clientDir.'/XXXXX9999A_ack.pdf', '%PDF');
        File::put($clientDir.'/computation.pdf', 'PAN details FGHIJ5678K attached');

        $pan = $this->metadata->resolvePanForClientFolder($clientDir);

        $this->assertSame('FGHIJ5678K', $pan);
    }

    public function test_extract_itr_metadata_finds_pan_and_ack(): void
    {
        $clientDir = $this->tempDir.'/Test Client';
        File::makeDirectory($clientDir, 0755, true);
        File::put($clientDir.'/ABCDE1234F_itr_ack.pdf', 'pdf');

        $meta = $this->metadata->extractItrMetadata($clientDir);

        $this->assertSame('ABCDE1234F', $meta['pan']);
        $this->assertNotNull($meta['ack_file']);
    }

    public function test_preview_service_classifies_create_and_update(): void
    {
        $importService = new \App\Services\NileshFolderImportService($this->metadata);
        $newDir = $this->tempDir.'/New Client Alpha';
        File::makeDirectory($newDir, 0755, true);
        File::put($newDir.'/FGHIJ5678K_ack.pdf', 'x');

        $preview = $importService->preview($this->tempDir);

        $this->assertArrayNotHasKey('error', $preview);
        $this->assertCount(1, $preview['create']);
        $this->assertSame('New Client Alpha', $preview['create'][0]['folder']);
    }
}
