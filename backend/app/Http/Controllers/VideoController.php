<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use FFMpeg\FFProbe;

class VideoController extends Controller
{

    public function show($id)
    {
        $video = Video::find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Vídeo não encontrado'
            ], 404);
        }

        return response()->json($video);
    }

    public function index()
    {
        $videos = Video::latest()->get();
        return response()->json($videos);
    }

    // Upload de vídeo
    public function upload(Request $request)
    {
        try {

            $request->validate([
                'video' => 'required|file|mimetypes:video/mp4,video/quicktime|max:512000',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            $file = $request->file('video');

            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs('uploads', $fileName, 'public');

            $videoPath = storage_path('app/public/' . $path);

            $ffprobe = FFProbe::create();

            $duration = (int) $ffprobe
                ->format($videoPath)
                ->get('duration');

            $url = '/api/videos/stream/' . $fileName;

            $video = Video::create([
                'title' => $request->title,
                'description' => $request->description ?? '',
                'url' => $url,
                'duration' => $duration,
                'user_id' => Auth::check() ? Auth::id() : 1
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vídeo enviado com sucesso',
                'video' => $video
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);

        }
    }

    // Deletar vídeo
    public function destroy($id)
    {
        $video = Video::find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Vídeo não encontrado'
            ], 404);
        }

        $path = str_replace('/storage/', '', $video->url);

        Storage::disk('public')->delete($path);

        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vídeo deletado com sucesso'
        ]);
    }

    // Streaming com suporte a Range Requests
    public function stream($filename)
    {
        $path = storage_path('app/public/uploads/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        $size = filesize($path);
        $start = 0;
        $end = $size - 1;

        $headers = [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
        ];

        if (isset($_SERVER['HTTP_RANGE'])) {

            preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);

            $start = intval($matches[1]);

            if (isset($matches[2])) {
                $end = intval($matches[2]);
            }

            $length = $end - $start + 1;

            $headers['Content-Length'] = $length;
            $headers['Content-Range'] = "bytes $start-$end/$size";

            $file = fopen($path, 'rb');
            fseek($file, $start);

            return response()->stream(function () use ($file, $length) {

                $buffer = 1024 * 8;
                $remaining = $length;

                while ($remaining > 0 && !feof($file)) {
                    $read = min($buffer, $remaining);
                    echo fread($file, $read);
                    flush();
                    $remaining -= $read;
                }

                fclose($file);

            }, 206, $headers);
        }

        return response()->file($path, $headers);
    }
}