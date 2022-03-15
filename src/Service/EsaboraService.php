<?php

namespace App\Service;

use App\Entity\Signalement;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EsaboraService
{
    private ConfigurationService $config;
    private HttpClientInterface $httpClient;

    public function __construct(ConfigurationService $configurationService, HttpClientInterface $httpClient)
    {
        $this->config = $configurationService;
        $this->httpClient = $httpClient;
    }

    private function curl($method, $url, $body = [])
    {
        $token = $this->config->get()->getEsaboraToken();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
            ),
            CURLOPT_POSTFIELDS => json_encode($body),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function post()
    {
        $url = $this->config->get()->getEsaboraUrl();

        $response = $this->curl('POST', $url . '/modbdd/?task=doTreatment', [
            'treatmentName' => 'Import HISTOLOGE',
            'fieldList'=> [
                [
                    'fieldName'=>'Référence_Histloge',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Usager_Nom',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Usager_Prénom',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Usager_Mail',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Usager_Téléphone',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Usager_Numero',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Usager_Nom_Rue',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Usager_Adresse2',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Usager_CodePostal',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Usager_Ville',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Adresse_Numéro',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Adresse_Nom_Rue',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Adresse_CodePostal',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Adresse_Ville',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Adresse_Etage',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Adresse_Porte',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Adresse_Latitude',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Adresse_Longitude',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Dossier_Ouverture',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'Dossier_Commentaire',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'PJ_Observations',
                    'fieldValue'=>''
                ],
                [
                    'fieldName'=>'PJ_Documents',
                    'fieldValue'=>''
                ]
            ]
        ]);
        echo $response;

    }

    public function get(Signalement $signalement)
    {
        $url = $this->config->get()->getEsaboraUrl();

        $response = $this->curl('POST', $url . '/mult/?task=doSearch', [
            'searchName' => 'WS_ETAT_DOSSIER_SAS',
            'criterionList' => [
                'criterionName' => 'SAS_Référence',
                'criterionValueList' => ['"' . $signalement->getUuid() . '"']
            ]
        ]);
        echo $response;

    }
}