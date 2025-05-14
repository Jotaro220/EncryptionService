<?php

namespace Tests\Feature;


use Tests\TestCase;
use App\Http\Controllers\BlowfishController;
use App\BlowfishCrypt\Blowfish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Helpers\KeyGeneratorApi;
use Illuminate\Support\Facades\Http;



class TestableBlowfish extends Blowfish
{
    public function testSplitBlock($block)
    {
        return $this->split_block($block);
    }
    
    public function testJoinWords($left, $right)
    {
        return $this->join_words($left, $right);
    }

    public function testPad($data, $block_size) {
        return $this->pad($data, $block_size);
    }

    public function testUnpad($data) {
        return $this->unpad($data);
    }
}

class BlowfishControllerTest extends TestCase
{
    protected $controller;
    protected $testFile;
    protected $encryptedFile;
    protected $decryptedFile;
    protected $blowfish;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new BlowfishController();
        $this->blowfish = new TestableBlowfish();
        
        
        Http::fake([
            'http://127.0.0.1:8000/api/v1/key/generate' => Http::response([
                'key' => 'mysecretkey',
                'id' => 1
            ], 201),
            
            'http://127.0.0.1:8000/api/v1/key/get_key' => Http::response([
                'key' => 'mysecretkey'
            ], 200)
        ]);

        Storage::fake('local');
        $this->testFile = storage_path('app/testfile.txt');
        file_put_contents($this->testFile, 'This is a test file content for encryption testing.');
        
        $this->encryptedFile = $this->testFile . '_encrypted';
        $this->decryptedFile = storage_path('app/Out_testfile.txt');
    }

    protected function tearDown(): void
    {
        
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        if (file_exists($this->encryptedFile)) {
            unlink($this->encryptedFile);
        }
        if (file_exists($this->decryptedFile)) {
            unlink($this->decryptedFile);
        }
        
        parent::tearDown();
    }


    public function testValidatesRequestParameters()
    {
        $request = new Request();
        
        $validator = Validator::make([], [
            'user_id' => 'required|integer',
            'path' => 'required|string',
            'action' => 'required|in:encrypt,decrypt',
        ]);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('path', $validator->errors()->toArray());
        $this->assertArrayHasKey('action', $validator->errors()->toArray());
    }

    public function testReturnsErrorForNonexistentFile()
    {
        $request = new Request([
            'user_id' => 1,
            'path' => '/nonexistent/file.txt',
            'action' => 'encrypt',
        ]);
        
        $response = $this->controller->validApi($request);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('File not found', $response->getData()->message);
    }

    public function testEncryptsFileSuccessfully()
    {
        $request = new Request([
            'user_id' => 1,
            'path' => $this->testFile,
            'action' => 'encrypt',
        ]);
        
        $response = $this->controller->validApi($request);
        
        $this->assertFileExists($this->encryptedFile);
        $this->assertEquals($this->encryptedFile, $response->getData()->FilePath);
        
        $this->assertNotEquals(
            file_get_contents($this->testFile),
            file_get_contents($this->encryptedFile)
        );
    }

    public function testDecryptsFileSuccessfully()
    {
        $encryptRequest = new Request([
            'user_id' => 1,
            'path' => $this->testFile,
            'action' => 'encrypt',
        ]);
        $encryptResponse = $this->controller->validApi($encryptRequest);
        
        $decryptRequest = new Request([
            'user_id' => 1,
            'path' => $this->encryptedFile,
            'action' => 'decrypt',
        ]);
        $decryptResponse = $this->controller->validApi($decryptRequest);
        
        $this->assertFileExists($this->decryptedFile);
        $this->assertEquals($this->decryptedFile, $decryptResponse->getData()->FilePath);
        
        $this->assertEquals(
            file_get_contents($this->testFile),
            file_get_contents($this->decryptedFile)
        );
    }


    public function testReturnsErrorForInvalidAction()
    {
        $request = new Request([
            'user_id' => 1,
            'path' => $this->testFile,
            'action' => 'invalid_action',
        ]);
        
        $response = $this->controller->validApi($request);
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Action is not recognized', $response->getData()->message);
    }

    public function testSplitBlockWorksCorrectly()
    {
        $block = 0x0123456789ABCDEF;
        $result = $this->blowfish->testSplitBlock($block);
        
        $this->assertEquals([0x01234567, 0x89ABCDEF], $result);
    }

    public function testJoinWordsWorksCorrectly()
    {
        $left = 0x01234567;
        $right = 0x89ABCDEF;
        $result = $this->blowfish->testJoinWords($left, $right);
        
        $this->assertEquals(0x0123456789ABCDEF, $result);
    }

    public function testWritesCorrectIdToEncryptedFile()
    {
    $request = new Request([
        'user_id' => 1,
        'path' => $this->testFile,
        'action' => 'encrypt',
    ]);
    
    $response = $this->controller->validApi($request);
    

    $fileHandle = fopen($this->encryptedFile, 'rb');
    $idBytes = fread($fileHandle, 4);
    fclose($fileHandle);
    

    $writtenId = unpack('L', $idBytes)[1];
    

    $this->assertEquals(0x1, $writtenId);
    }

    public function testReadsCorrectIdFromEncryptedFile()
    {

        $encryptRequest = new Request([
            'user_id' => 1,
            'path' => $this->testFile,
            'action' => 'encrypt',
        ]);
        $this->controller->validApi($encryptRequest);
        

        $fileHandle = fopen($this->encryptedFile, 'rb');
        $idBytes = fread($fileHandle, 4);
        fclose($fileHandle);
        
        $readId = unpack('L', $idBytes)[1];
        

        $decryptRequest = new Request([
            'user_id' => 1,
            'path' => $this->encryptedFile,
            'action' => 'decrypt',
        ]);
        $response = $this->controller->validApi($decryptRequest);
        

        $this->assertFileExists($this->decryptedFile);
        $this->assertEquals(
            file_get_contents($this->testFile),
            file_get_contents($this->decryptedFile)
        );
        

        $this->assertEquals(0x1, $readId);
    }
}