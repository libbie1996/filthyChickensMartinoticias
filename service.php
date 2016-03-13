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

		private function urlSplit($url) {
			$url = explode("/", trim($url));
			unset($url[0]);
			unset($url[1]);
			unset($url[2]);
			return implode("/", $url);
		}

		public function _main(Request $request)
		{

			print_r($this->urlSplit("http://www.martinoticias.com/content/dia-mundial-contra-la-censura-en-internet-apunta-a-cuba/117177.html"));
			exit;

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
				$response->createFromTemplate("allStories.tpl", $responseContent);
				return $response;
			}


			// story
			if(explode(" ", strtoupper(trim($request->query)))[0] == "STORY")
			{
				// create a new client
				$client = new Client();
				$guzzle = $client->getClient();
				$guzzle->setDefaultOption('verify', false);
				$client->setClient($guzzle);

				// create a crawler
				$crawler = $client->request('GET', "http://www.martinoticias.com/" . explode(" ", trim($request->query))[1]);

				// search for result
				$title = $crawler->filter('#article h1')->text();
				$intro = $crawler->filter('#article .article_txt_intro')->text();
				$imgUrl = $crawler->filter('#article .contentImage img')->attr("src");
				$content = $crawler->filter('#article .articleContent .zoomMe p')->each(function($content, $i) {
					return $content->text();
				});;

				$imgName = $this->utils->generateRandomHash() . "." . explode(".", explode("/", trim($imgUrl))[count(explode("/", trim($imgUrl))) - 1])[count(explode(".", explode("/", trim($imgUrl))[count(explode("/", trim($imgUrl))) - 1])) - 1];
				$img = \Phalcon\DI\FactoryDefault::getDefault()->get('path')['root'] . "/temp/$imgName";
				file_put_contents($img, file_get_contents($imgUrl));
				$this->utils->optimizeImage($img, 600);

				// create a json object to send to the template
				$responseContent = array(
					"title" => $title,
					"intro" => $intro,
					"img" => $img,
					"content" => $content
				);

				// create the response
				$response = new Response();
				$response->setResponseSubject("[RESPONSE_EMAIL_SUBJECT]");
				$response->createFromTemplate("story.tpl", $responseContent);
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

			if (explode(" ", strtoupper(trim($request->query)))[0] == "CATEGORY") {
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
