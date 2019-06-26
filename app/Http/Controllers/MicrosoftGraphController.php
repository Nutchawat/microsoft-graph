<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use App\TokenStore\TokenCache;

class MicrosoftGraphController extends Controller
{
  protected $graph;
  protected $headerTimezone;

  public function __construct()
  {
    $tokenCache = new TokenCache();
    $this->headerTimezone = [ "Prefer" => "outlook.timezone=\"Pacific Standard Time\"" ];
    $this->graph = new Graph();
    $this->graph->setAccessToken($tokenCache->getAccessToken());
    $this->bodyTemplate = [
      "subject" => "",
      "body" => [
        "contentType" => "HTML",
        "content" => ""
      ],
      "start" => [
          "dateTime" => "",
          "timeZone" => "Pacific Standard Time"
      ],
      "end" => [
          "dateTime" => "",
          "timeZone" => "Pacific Standard Time"
      ],
      "attendees" => [
        [
          "emailAddress" => [
            "address" => "",
            "name" => ""
          ],
          "type" => "required"
        ]
      ]
    ];
  }

  private function getUser($email)
  {
    $user = $this->graph->createRequest("GET", "/users/".$email)
                  ->setReturnType(Model\User::class)
                  ->execute();

    return $user;
  }

  private function getBodyEvent($req)
  {
    $user = $this->getUser($req["email"]);

    $body = $this->bodyTemplate;
    $body["subject"] = "Leave Event of K.".$user->getDisplayName();
    $body["body"]["content"] = $req["deslereq"];
    $body["start"]["dateTime"] = $req["dtestrt"]."T".$req["timstrt"].":00";
    $body["end"]["dateTime"] = $req["dteend"]."T".$req["timend"].":00";
    $body["attendees"][0]["emailAddress"]["address"] = $user->getMail();
    $body["attendees"][0]["emailAddress"]["name"] = $user->getDisplayName();

    return $body;
  }

  public function getEventAll($email)
  {
    $events = $this->graph->createRequest("GET", "/users/".$email."/events")
                  ->addHeaders($this->headerTimezone)
                  ->setReturnType(Model\Event::class)
                  ->execute();

    return response($events, 200);
  }

  public function getEventIDAll($email)
  {
    $response = [];

    $events = $this->graph->createRequest("GET", "/users/".$email."/events")
                  ->addHeaders($this->headerTimezone)
                  ->setReturnType(Model\Event::class)
                  ->execute();

    foreach ($events as $key => $event)
    {
      $response[$key] = [];
      $response[$key]["event_id"] = $event->getID();
    }
    return response($response, 200);
  }

  public function getEvents(Request $request, $email)
  {
    $reqs = $request->all();
    $response = [];

    foreach($reqs as $key => $req)
    {
      $event = $this->graph->createRequest("GET", "/users/".$email."/events/".$req["event_id"])
                    ->addHeaders($this->headerTimezone)
                    ->setReturnType(Model\Event::class)
                    ->execute();

      $response[$key] = $event;
    }
    return response($response, 200);
  }

  public function createEvents(Request $request, $email)
  {
    $reqs = $request->all();
    $response = [];

    foreach($reqs as $key => $req)
    {
      $body = $this->getBodyEvent($req);

      $event = $this->graph->createRequest("POST", "/users/".$email."/events")
                    ->addHeaders($this->headerTimezone)
                    ->attachBody($body)
                    ->setReturnType(Model\Event::class)
                    ->execute();

      $response[$key] = $req;
      $response[$key]["email"] = $req["email"];
      $response[$key]["event_id"] = $event->getID();
    }

    return response($response, 200);
  }

  public function updateEvents(Request $request, $email)
  {
    $reqs = $request->all();

    foreach($reqs as $key => $req)
    {
      $body = $this->getBodyEvent($req);

      $event = $this->graph->createRequest("PATCH", "/users/".$email."/events/".$req["event_id"])
                    ->addHeaders($this->headerTimezone)
                    ->attachBody($body)
                    ->setReturnType(Model\Event::class)
                    ->execute();
    }

    return response("Update Events Success", 200);
  }

  public function deleteEvents(Request $request, $email)
  {
    $reqs = $request->all();

    foreach($reqs as $key => $req)
    {
      $this->graph->createRequest("DELETE", "/users/".$email."/events/".$req["event_id"])
                  ->execute();
    }

    return response("Delete Events Success", 200);
  }
}