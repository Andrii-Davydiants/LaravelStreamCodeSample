<?php


namespace App\Services;


use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class LiveStreamService
{
    private $live_host = '';

    private $path = [
        'create' => '/create',
        'connect' => '/join',
        'end' => '/delete',
        'viewers' => '/getAll'
    ];

    public function __construct()
    {
        $this->live_host = config('live-stream.host');

        $this->live_host = trim($this->live_host, '/');
    }

    public function create()
    {
        $response = Http::get($this->live_host . $this->path['create']);

        if($response->ok())
        {
            $data = $response->json();

            if(isset($data['room_id'])) return $data['room_id'];
        }

        throw new \Exception("Streaming service unavailable");
    }

    public  function makeUrl($room_id)
    {
        return $this->live_host . $this->path['connect'] . '?roomID=' . $room_id . '';
    }

    public function checkStreamAvailability($lastSteam_at)
    {
        $dateLastStream = Carbon::createFromDate($lastSteam_at);
        $now = Carbon::now();
        $testdate = $dateLastStream->diffInHours($now);

        return ($testdate < 24) ? false : true;
    }

    public function end($room_id)
    {
        $response = Http::get($this->live_host . $this->path['end'] . '?roomID='. $room_id . '');

        if(!$response->ok()) throw new \Exception('Stream service does not answer');

        $data = $response->json();

        return $data['participants'];
    }

    public function saveStreamRecord()
    {
        // closed
    }

    public function getViewers()
    {
        $response = Http::get($this->live_host . $this->path['viewers']);

        if(!$response->ok()) throw new \Exception('Stream service does not answer');

        $data = $response->json();

        return $data['allRooms'];
    }
}
