<?php
class PhpGyazo_Tests_Functional_ApplicationTest extends Sumile_WebTestCase
{
    private $db;

    public function setUp()
    {
        parent::setUp();

        $this->db = new PDO('mysql:host=localhost', 'root');

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
        $app = new PhpGyazo_Application;

        $app['db.host']     = 'localhost';
        $app['db.user']     = 'root';
        $app['db.password'] = '';
        $app['db.database'] = 'php_gyazo_dev';

        return $app;
    }
}
