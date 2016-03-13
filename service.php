<?php

	use Goutte\Client; // UNCOMMENT TO USE THE CRAWLER OR DELETE

	class Martinoticias extends Service
	{
		/**
		 * Function executed when the service is called
		 *
		 * @param Request
		 * @return Response
		 * */


		public function _main(Request $request)
		{

			if(empty($request->query)) {
				// create a new client
				$client = new Client();
				$guzzle = $client->getClient();
				$guzzle->setDefaultOption('verify', false);
				$client->setClient($guzzle);
	
				// create a crawler
				$crawler = $client->request('GET', "http://www.martinoticias.com/api/epiqq");
	
				// search for result

				$title = $crawler->filter('item title')->each(function($title, $i) {
					return $title->text();
				});
				$description = $crawler->filter('item description')->each(function($description, $i) {
					return $description->text();
				});
				$link = $crawler->filter('item link')->each(function($link, $i) {
					return "http://127.0.0.1:8080/run/display?subject=martinoticias {$link->text()}";
				});
				$pubDate = $crawler->filter('item pubDate')->each(function($pubDate, $i) {
					return $pubDate->text();
				});
				$category = $crawler->filter('item')->each(function($item, $i) {
					return $item->filter('category')->each(function($category, $i) {
						return $category->text();
					});
				});
				$author = $crawler->filter('item')->each(function($item, $i) {
					if ($item->filter('author')->count() == 0) {
						return "desconocido";
					} else {
						$authorString = explode(" ", trim($item->filter('author')->text()));
						return substr($authorString[1], 1, strpos($authorString[1], ")") - 1) . " ({$authorString[0]})";
					}
				});

				$categoryLink = array();

				for ($i=0; $i < count($title); $i++) { 
					$categoryLink[$i] = array();
					foreach ($category[$i] as $currCategory) {
						$categoryLink[$i][] = "http://127.0.0.1:8080/run/display?subject=martinoticias category $currCategory";
					}
				}

				// create a json object to send to the template
				$responseContent = array(
					"title" => $title,
					"description" => $description,
					"link" => $link,
					"pubDate" => $pubDate,
					"category" => $category,
					"categoryLink" => $categoryLink,
					"author" => $author
				);
	
				// create the response
				$response = new Response();
				$response->setResponseSubject("[RESPONSE_EMAIL_SUBJECT]");
				$response->createFromTemplate("basic.tpl", $responseContent);
				return $response;
			}


			// headline
			if($request->query == "headline")
			{
				// create a new client
				$client = new Client();
				$guzzle = $client->getClient();
				$guzzle->setDefaultOption('verify', false);
				$client->setClient($guzzle);
	
				// create a crawler
				$crawler = $client->request('GET', "http://www.martinoticias.com/api/z_uqverpov");
	
				// search for result

				$titles = $crawler->filter('item title')->each(function($title, $i) {
					return $title->text();
				});
				$description = $crawler->filter('item description')->each(function($description, $i) {
					return $description->text();
				});
				$link = $crawler->filter('item link')->each(function($link, $i) {
					return "http://127.0.0.1:8080/run/display?subject=martinoticias {$link->text()}";
				});
				$pubDate = $crawler->filter('item pubDate')->each(function($pubDate, $i) {
					return $pubDate->text();
				});

				// create a json object to send to the template
				$responseContent = array(
					"titles" => $titles,
					"description" => $description,
					"link" => $link,
					"pubDate" => $pubDate
				);
	
				// create the response
				$response = new Response();
				$response->setResponseSubject("[RESPONSE_EMAIL_SUBJECT]");
				$response->createFromTemplate("basic.tpl", $responseContent);
				return $response;
			}

			// template
			if($request->query == "template") {
				// create a new client
				$client = new Client();
				$guzzle = $client->getClient();
				$guzzle->setDefaultOption('verify', false);
				$client->setClient($guzzle);
	
				// create a crawler
				$crawler = $client->request('GET', "http://www.martinoticias.com/api/epiqq");
	
				// search for result
				$title = $crawler->filter('channel title')->text();
				$description = $crawler->filter('channel description')->text();
				$link = $crawler->filter('channel link')->text();
			
				// create a json object to send to the template
				$responseContent = array(
					"title" => $title,
					"description" => $description,
					"link" => $link
				);
	
				// create the response
				$response = new Response();
				$response->setResponseSubject("[RESPONSE_EMAIL_SUBJECT]");
				$response->createFromTemplate("basic.tpl", $responseContent);
				return $response;
			}
		}

		public function addItemToArray($items, $item) {
			$items[] = $item;
			return $items;
		}
	}
?>