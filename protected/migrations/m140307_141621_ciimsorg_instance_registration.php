<?php

class m140307_141621_ciimsorg_instance_registration extends CDbMigration
{
	public function safeUp()
	{
	    $curl = curl_init();

        // Retrieve the current URL either from the database (if a submission has been set) or from the installer's hostname file
        Yii::import('application.models.Configuration');
        $url = Configuration::model()->findByAttributes(array('key' => 'hostname'));

        if ($url == NULL)
        {
            $hostnameFile = Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . 'hostname';
            if (file_exists($hostnameFile))
            {
                $url = new StdClass;
                $url->value = preg_replace("/\s+/", "", @file_get_contents($hostnameFile));
            }
            else
            {
                echo 'CiiMS\' base url is not set within the configuration. Please see the UPGRADING guide before attempting to migrate your application';
                return false;
            }
        }

        // Submit the data
        $data = array(
            'name' => Cii::getConfig('name'),
            'url' => $url->value
        );

        curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS => CJSON::encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',                                                                                
                    'Content-Length: ' . strlen(CJSON::encode($data))
                ),
                CURLOPT_URL => 'https://www.ciims.org/customize/default/registration',
                CURLOPT_CAINFO => Yii::getPathOfAlias('application.config.certs') . DIRECTORY_SEPARATOR . 'GeoTrustGlobalCA.cer'
        ));

        $response = CJSON::decode(curl_exec($curl));
        
        if ($response['status'] == 200)
        {
            $instance = new Configuration;
            $instance->attributes = array('key' => 'instance_id', 'value' => Cii::get($response['response'], 'instance_id'));
            $token = new Configuration;
            $token->attributes = array('key' => 'token', 'value' => Cii::get($response['response'], 'token'));

            if (!$token->save())
                return false;

            if (!$instance->save())
                return false;

            return true;
        }

        return false;
    }

	public function safeDown()
	{
		echo "m140307_141621_ciimsorg_instance_registration does not support migration down.\n";
		return false;
	}
}