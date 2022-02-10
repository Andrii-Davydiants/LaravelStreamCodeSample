<?php

namespace App\Http\Controllers;

use App\Dto\LiveStreamPostDto;
use App\Facades\LiveStream;
use App\Http\Requests\LiveStream\CreateLiveStreamRequest;
use App\Http\Requests\LiveStream\EndLiveStreamRequest;
use App\Http\Requests\LiveStream\PostLiveStreamRequest;
use App\Http\Resources\LiveStreamResource;
use App\Http\Resources\LiveStreamResources;
use App\Jobs\EndLiveStreamJob;
use App\Repositories\DB\LiveStreamRepository;

class LiveStreamController extends Controller
{
    private $repository;

    public function __construct(LiveStreamRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $liveStreamSubscriptions = $this->repository->subscriptionsLiveStream();

        $liveStreamSecondSubscriptions = $this->repository->subscriptionsOfSubscriptionsLiveStream();

        return new LiveStreamResources(['subscriptions' => $liveStreamSubscriptions, 'subscriptionOfSubscriptions' => $liveStreamSecondSubscriptions]);
    }

    public function show($id)
    {
        $stream = $this->repository->getStream($id);

        if(!is_null($stream->ended_at)) throw new \Exception('Stream is ended');

        return new LiveStreamResource($stream);
    }

    public function create(CreateLiveStreamRequest $request)
    {
        $room_id = LiveStream::create();

        $stream = $this->repository->store($request->title, $request->description, $room_id);

        //send notification about stream start

        $this->dispatch((new EndLiveStreamJob($stream))->delay(config('live-stream.max-time')));

        return new LiveStreamResource($stream);
    }

    public function endLiveStream(EndLiveStreamRequest $request)
    {
        $room_id = $this->repository->endStream($request->id);

        $users = LiveStream::end($room_id);

        $this->repository->streamUsers($request->id, $users);

        return response()->json([], 200);
    }

    public function makePostFromStream(PostLiveStreamRequest $request)
    {
        $streamObj = new LiveStreamPostDto($request);

        LiveStream::saveStreamRecord($streamObj);

        //LiveStream::notifyUsers($post_id, $this->repository->getViewedUsers($users));
    }
}
