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

		private function listArticles(Request $request, $query)
		{
			// Setup client
			$client = new Client();

			// Setup crawler
			$crawler = $client->request('GET', "http://www.martinoticias.com/api/epiqq");

			// Keep integral part of query
			$query = explode(" ", $query);
			array_shift($query);
			$query = implode(" ", $query);

			// Collect articles by category
			$articles = array();

			$crawler->filter('channel item')->each(function($item, $i) use (&$articles, $query) {

				//If category matches, add to list of articles
				$item->filter('category')->each(function($cat, $i) use (&$articles, $query, $item) {
					if (strtoupper($cat->text()) == strtoupper($query)) {
						$title = $item->filter('title')->text();
						$link = $item->filter('link')->text();
						$pubDate = $item->filter('pubDate')->text();
						$description = $item->filter('description')->text();

						$articles[] = array(
							"title"       => $title,
							"link"        => $link,
							"pubDate"     => $pubDate,
							"description" => $description
						);
					}
				});
			});

			// Return response content
			$responseContent = array(
				"articles" => $articles
			);
			return $responseContent;
		}

		public function _main(Request $request)
		{

			if(empty($request->query))
			{
				// create a new client
				$client = new Client();
				$guzzle = $client->getClient();
				$guzzle->setDefaultOption('verify', false);
				$client->setClient($guzzle);

				// create a crawler
				$crawler = $client->request('GET', "http://www.martinoticias.com/api/epiqq");

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

			if (strpos($request->query, "category") > -1) {
				$responseContent = $this->listArticles($request, $request->query);
				$response = new Response();
				$response->setResponseSubject("[RESPONSE_EMAIL_SUBJECT]");
				$response->createFromTemplate("cat_articles.tpl", $responseContent);
				return $response;
			}
		}

		public function addItemToArray($items, $item) {
			$items[] = $item;
			return $items;
		}
	}
?>
