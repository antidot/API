<?php
require_once "afs_connector_interface.php";
require_once "afs_connector_base.php";
require_once "afs_service.php";

/** @brief AFS search connector.
 *
 * Only one object of this type should be instanciated in each PHP integration.
 */
class AfsSearchConnector extends AfsConnectorBase implements AfsConnectorInterface
{
    protected $scheme;
    protected $host;
    protected $service;

    /** @brief Construct new search connector.
     *
     * All parameter values should have been provided by Antidot.
     *
     * @param $host [in] server hosting the required service.
     * @param $service [in] Antidot service (see @a AfsService).
     * @param $scheme [in] Scheme for the connection URL see
     *        @ref uri_scheme (default: @a AFS_SCHEME_HTTP).
     *
     * @exception InvalidArgumentException invalid scheme parameter provided.
     */
    public function __construct($host, AfsService $service,
        $scheme=AFS_SCHEME_HTTP)
    {
        if ($scheme != AFS_SCHEME_HTTP) {
            throw InvalidArgumentException('Search connector support only HTTTP'
                . ' connection');
        }
        $this->scheme = $scheme;
        $this->host = $host;
        $this->service = $service;
    }

    /** @brief Send a query.
     *
     * Query is built using provided @a parameters.
     * @param $parameters [in] list of parameters used to build the query.
     * @return JSON decoded reply of the query.
     */
    public function send(array $parameters)
    {
        $url = $this->build_url($parameters);
        $request = curl_init($url);
        if ($request == false) {
            $result = $this->build_error('Cannot initialize connexion', $url);
        } else {
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($request, CURLOPT_FAILONERROR, true);
            $result = curl_exec($request);
            if ($result == false) {
                $result = $this->build_error('Failed to execute request',  $url);
            }
            curl_close($request);
        }
        return json_decode($result);
    }

    protected function build_url(array $parameters)
    {
        $parameters['afs:service'] = $this->service->id;
        $parameters['afs:status'] = $this->service->status;
        $parameters['afs:output'] = 'json,2';
        return sprintf('%s://%s/search?%s', $this->scheme, $this->host,
            $this->format_parameters($parameters));
    }

    private function build_error($message, $details)
    {
        error_log("$message [$details]");
        return '{ "header": { "error": { "message": [ "' . $message . '" ] } } }';
    }
}

?>