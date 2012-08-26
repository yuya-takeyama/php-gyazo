<?php
class PhpGyazo_Tests_Functional_ApplicationTest extends Sumile_WebTestCase
{
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
