<?php
class ApiCaller {
	/**
	 * to get some values with curl
	 *
	 * @param string url cible
	 * @param array data the data you want to send to the server
	 * @return string content of the get request
	 */
    public function call($method, $url, $data = false, $additionalHeaders = false) {
		// Tableau contenant les options de téléchargement
		$options=array(
			CURLOPT_URL				=> $url, // Url cible (l'url la page que vous voulez télécharger)
			CURLOPT_RETURNTRANSFER 	=> true, // Retourner le contenu téléchargé dans une chaine (au lieu de l'afficher directement)
			CURLOPT_HEADER         	=> false // Ne pas inclure l'entête de réponse du serveur dans la chaine retournée
		);

		$headers = array(
			"X-DreamFactory-Application-Name: cowaboo"
		);

		if ($additionalHeaders) {
			foreach ($additionalHeaders as $name => $value) {
				$headers[] = $name.": ".$value;
			}
		}

		$method = strtolower($method);
		if ($method == 'post') {
			//set the url, number of POST vars, POST data
			$options[CURLOPT_POST]       = count($data);
			if (is_array($data)) {
				$data = http_build_query($data);
			}
			$options[CURLOPT_POSTFIELDS] = $data;
		}

		// Création d'un nouvelle ressource cURL
		$CURL=curl_init();

		// Configuration des options de téléchargement
		curl_setopt_array($CURL, $options);
		curl_setopt($CURL, CURLOPT_HTTPHEADER, $headers);
curl_setopt($CURL, CURLOPT_HTTPAUTH, CURLAUTH_NTLM); 

		// Exécution de la requête
		$content = curl_exec($CURL);      // Le contenu téléchargé est enregistré dans la variable $content. Libre à vous de l'afficher.

		// Fermeture de la session cURL
		curl_close($CURL);

	    return $content;
	}
}
?>