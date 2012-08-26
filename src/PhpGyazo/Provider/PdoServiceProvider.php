<?php
class PhpGyazo_Provider_PdoServiceProvider implements Sumile_ServiceProviderInterface
{
    public function register(Sumile_Application $app)
    {
        $app['db'] = $app->share(array($this, 'providePdo'));
    }

    public function boot(Sumile_Application $app)
    {
    }

    public function providePdo($c)
    {
        return new PDO("mysql:dbname={$c['db.database']};host={$c['db.host']}", $c['db.user'], $c['db.password'], array(
            PDO::MYSQL_ATTR_DIRECT_QUERY => true,
        ));
    }
}
