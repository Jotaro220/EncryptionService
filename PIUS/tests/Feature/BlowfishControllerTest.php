<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlowfishControllerTest extends TestCase
{

    /** @test */
    public function it_encrypts_a_file_and_creates_encrypted_file()
    {

        $absoluteFilePath = "/home/miet/Test/test.txt";
    
        // Вызываем метод шифрования
        $response = $this->get("/blowfish/encrypt?user_id=12345678&path=$absoluteFilePath&action=encrypt");
    
        // Проверяем, что ответ успешный
        $response->assertStatus(200);
    
        // Проверяем, что зашифрованный файл создан
        $encryptedFilePath = $response->json('encryptedFilePath');
        $this->assertFileExists($encryptedFilePath);
    
        // Проверяем, что зашифрованный файл не пустой
        $this->assertGreaterThan(0, filesize($encryptedFilePath));
    }


    /** @test */
    public function it_returns_error_if_file_not_found()
    {
        // Вызываем метод шифрования с несуществующим файлом
        $response = $this->get("/blowfish/encrypt?user_id=123&path=/invalid/path.txt&action=encrypt");

        // Проверяем, что возвращается ошибка 401
        $response->assertStatus(400);

        // Проверяем сообщение об ошибке
        $response->assertJson([
            'code' => 400,
            'message' => 'File not found',
        ]);
    }

    /** @test */
    public function it_returns_error_if_action_is_invalid()
    {

        $absoluteFilePath = "/home/miet/Test/test.txt";

        // Вызываем метод с неверным действием
        $response = $this->get("/blowfish/encrypt?user_id=123&path=storage/app/$absoluteFilePath&action=invalid_action");

        // Проверяем, что возвращается ошибка 400
        $response->assertStatus(400);

        // Проверяем сообщение об ошибке
        $response->assertJson([
            'code' => 400,
            'message' => 'Incorrect input data',
        ]);
    }

    /** @test */
    public function it_returns_error_if_user_id_is_missing()
    {
        // Создаем временный файл для теста
        $absoluteFilePath = "/home/miet/Test/test.txt";

        // Вызываем метод без user_id
        $response = $this->get("/blowfish/encrypt?path=storage/app/$absoluteFilePath&action=encrypt");

        // Проверяем, что возвращается ошибка 400
        $response->assertStatus(400);

        // Проверяем сообщение об ошибке
        $response->assertJson([
            'code' => 400,
            'message' => 'Incorrect input data',
        ]);
    }

    /** @test */
public function it_encrypts_a_file_and_creates_encrypted_file_with_correct_id()
{

    $absoluteFilePath = "/home/miet/Test/test.txt";

    // Вызываем метод шифрования
    $response = $this->get("/blowfish/encrypt?user_id=123&path=$absoluteFilePath&action=encrypt");

    // Проверяем, что ответ успешный
    $response->assertStatus(200);

    // Проверяем, что зашифрованный файл создан
    $encryptedFilePath = $response->json('encryptedFilePath');
    $this->assertFileExists($encryptedFilePath);

    // Проверяем, что зашифрованный файл не пустой
    $this->assertGreaterThan(0, filesize($encryptedFilePath));

    // Проверяем, что первые 5 байт содержат корректный ID
    $encryptedFileHandle = fopen($encryptedFilePath, 'rb');
    $idBytes = fread($encryptedFileHandle, 5); // Читаем первые 5 байт
    fclose($encryptedFileHandle);

    // Ожидаемый ID (в данном примере 0x123456789A)
    $expectedId = 0x123456789A;
    $expectedIdBytes = pack('J', $expectedId); // Преобразуем ID в байты
    $expectedIdBytes = substr($expectedIdBytes, 0, 5); // Берем первые 5 байт

    // Сравниваем первые 5 байт с ожидаемым ID
    $this->assertEquals($expectedIdBytes, $idBytes, 'Первые 5 байт файла не совпадают с ожидаемым ID');
}
}