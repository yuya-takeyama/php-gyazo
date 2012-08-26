<?php
class PhpGyazo_Tests_Functional_ApplicationTest extends Sumile_WebTestCase
{
    const FIXTURE_FILE     = 'tests/fixtures/picture.png';
    const FIXTURE_FILE_MD5 = 'fbe459130020268492b84eaec1bf8101';

    private $db;

    public function setUp()
    {
        parent::setUp();

        $this->db = new PDO('mysql:dbname=php_gyazo_test;host=localhost', 'root');

        $this->db->exec('DROP TABLE IF EXISTS pictures');
        $this->db->exec(file_get_contents('sql/pictures.sql'));
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function root_should_be_not_found()
    {
        $res = $this->get('/');

        $this->assertTrue($res->isNotFound());
    }

    /**
     * @test
     */
    public function picture_upload_returns_page_url_when_succeeded()
    {
        $res = $this->post('/upload.cgi', array(
            'post'  => array(
                'id' => 'testuser',
            ),
            'files' => array(
                'imagedata' => self::FIXTURE_FILE,
            ),
        ));

        $this->assertEquals('http://localhost/' . self::FIXTURE_FILE_MD5, $res->body());
        $this->assertTrue($res->isOk());

        $res = $this->get('/' . self::FIXTURE_FILE_MD5);

        $this->assertTrue($res->isOk());
        $this->assertContains('<img src="/' . self::FIXTURE_FILE_MD5 . '.png" alt="Picture" />', $res->body());

        $res = $this->get('/' . self::FIXTURE_FILE_MD5 . '.png');

        $this->assertTrue($res->isOk());
        $this->assertEquals(file_get_contents(self::FIXTURE_FILE), $res->body());
    }

    /**
     * @test
     */
    public function upload_without_user_id_is_not_ok()
    {
        $res = $this->post('/upload.cgi', array(
            'files' => array(
                'imagedata' => self::FIXTURE_FILE,
            ),
        ));

        $this->assertFalse($res->isOk());
        $this->assertContains('Failed to upload', $res->body());
    }

    /**
     * @test
     */
    public function upload_without_file_is_not_ok()
    {
        $res = $this->post('/upload.cgi', array(
            'post' => array(
                'user_id' => 'test_user',
            ),
        ));

        $this->assertFalse($res->isOk());
        $this->assertEquals('Failed to upload: No image file is attached', $res->body());
    }

    /**
     * @test
     */
    public function invalid_page_should_be_not_found()
    {
        $res = $this->get('/invalid_page');

        $this->assertTrue($res->isNotFound());
    }

    /**
     * @test
     */
    public function invalid_picture_should_be_not_found()
    {
        $res = $this->get('/invalid_page.png');

        $this->assertTrue($res->isNotFound());
    }

    public function createApplication()
    {
        return new PhpGyazo_Application;
    }
}
