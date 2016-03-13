<?php

	use Goutte\Client; // UNCOMMENT TO USE THE CRAWLER OR DELETE

	class Martinoticias extends Service {

		/**
		 * Searches for articles using the search API on Martinoticias
		 */
		private function searchArticles($query)
		{
			$apiUrl = "http://www.martinoticias.com/post.api?";

			// Setup client
			$client = new Client();

			// Keep integral part of query
			$query = explode(" ", $query);
			array_shift($query);
			$rowLimit = 20;

			// Set starting row
			if (count($query) > 1)
				$rowLimit = array_shift($query);

			// Limit amount of articles to return
			if (count($query) > 1)
				$increment = array_shift($query);

			$query = urlencode(implode(" ", $query));

			// Fetch json data from search API
			$apiData = file_get_contents($apiUrl . "&startrow=0&rowlimit=$rowLimit&searchtype=all&keywords=$query&zone=allzones&order=date");
			$jsonData = json_decode($apiData, true);

			// Fetch rows of data
			$totalRows = $jsonData['d']['postquery']['PrimaryQueryResult']['RelevantResults']['TotalRows'];
			$rows = $jsonData['d']['postquery']['PrimaryQueryResult']['RelevantResults']['Table']['Rows']['results'];

			$data = array();
			$i = 0;
			$articles = array();

			// Search through each row, fetch each cell, store as associative array.
			foreach ($rows as $row) {
				foreach ($row['Cells']['results'] as $cell) {
					$data[$cell['Key']] = $cell['Value'];
				}

				$author = $data['searchArticleAuthor'];
				if (strlen(trim($author)) < 1) $author = "desconcido";

				$articles[] = array(
					'pubDate'      => $data['searchArticlePubDate'],
					'description'  => $data['HitHighlightedSummary'],
					'category'     => explode(";", $data['searchArticleZone']),
					'title'        => $data['searchArticleTitle'],
					'tags'         => $data['searchArticleTag'],
					'author'       => $author,
					'link'         => implode("/",
										array("content",
										      $data['searchArticleSlug'],
											  $data['searchArticleId'])
									  )
				);
			}

			// Return response content
			$newLimit = $rowLimit + $increment;
			$responseContent = array(
				'articles'  => $articles,
				'totalRows' => $totalRows,
				'rows'      => $rowLimit,
				'more'      => $apiUrl . "&startrow=0&rowlimit=$newLimit&searchtype=all&keywords=$query&zone=allzones&order=date"
			);
			return $responseContent;
		}

		private function listArticles($query) {
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
						$author = "desconocido";
						if ($item->filter('author')->count() > 0) {
							$authorString = explode(" ", trim($item->filter('author')->text()));
							$author = substr($authorString[1], 1, strpos($authorString[1], ")") - 1) . " ({$authorString[0]})";
						}

						$articles[] = array(
							"title"       => $title,
							"link"        => $link,
							"pubDate"     => $pubDate,
							"description" => $description,
							"author"      => $author
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

		private function allStories($query) {
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
				return $this->utils->getLinkToService("MARTINOTICIAS", "STORY {$this->urlSplit($link->text())}");
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
			return $responseContent;
		}

		private function story($query) {
			// create a new client
			$client = new Client();
			$guzzle = $client->getClient();
			$guzzle->setDefaultOption('verify', false);
			$client->setClient($guzzle);

			// create a crawler
			$crawler = $client->request('GET', "http://www.martinoticias.com/" . explode(" ", trim($query))[1]);

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
			return $responseContent;
		}

		private function urlSplit($url) {
			$url = explode("/", trim($url));
			unset($url[0]);
			unset($url[1]);
			unset($url[2]);
			return implode("/", $url);
		}
		/**
		 * Function executed when the service is called
		 *
		 * @param Request
		 * @return Response
		 * */

		public function _main(Request $request) {
			if(empty($request->query)) {
				$responseContent = $this->allStories($request->query);
				$response = new Response();
				$response->setResponseSubject("[RESPONSE_EMAIL_SUBJECT]");
				$response->createFromTemplate("allStories.tpl", $responseContent);
				return $response;
			}

			if(explode(" ", strtoupper(trim($request->query)))[0] == "STORY") {
				$responseContent = $this->story($request->query);
				$response = new Response();
				$response->setResponseSubject("[RESPONSE_EMAIL_SUBJECT]");
				$response->createFromTemplate("story.tpl", $responseContent);
				return $response;
			}

			if (explode(" ", strtoupper(trim($request->query)))[0] == "CATEGORY") {
				$responseContent = $this->listArticles($request->query);
				$response = new Response();
				$response->setResponseSubject("[RESPONSE_EMAIL_SUBJECT]");
				$response->createFromTemplate("catArticles.tpl", $responseContent);
				return $response;
			}

			if (explode(" ", strtoupper(trim($request->query)))[0] == "SEARCH") {
				$responseContent = $this->searchArticles($request->query);
				$response = new Response();
				$response->setResponseSubject("[RESPONSE_EMAIL_SUBJECT]");
				$response->createFromTemplate("searchArticles.tpl", $responseContent);
				return $response;
			}
		}

		public function addItemToArray($items, $item) {
			$items[] = $item;
			return $items;
		}
	}
?>
