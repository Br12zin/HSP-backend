<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

class VideoController extends Controller
{

    // LISTAR VÍDEOS
    public function index()
    {
        $videos = Video::latest()->get();
        return response()->json($videos);
    }

    // MOSTRAR UM VÍDEO
    public function show($id)
    {
        $video = Video::findOrFail($id);
        return response()->json($video);
    }

    // CRIAR VÍDEO POR URL (youtube ou CDN)
    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'url' => 'required|string',
            'duration' => 'nullable|string'
        ]);

        $video = Video::create([
            'title' => $request->title,
            'description' => $request->description,
            'url' => $request->url,
            'duration' => $request->duration,
            'user_id' => auth::check() ? auth::id() : 1
        ]);

        return response()->json($video, 201);
    }

    // ATUALIZAR VÍDEO
    public function update(Request $request, $id)
    {
        $video = Video::findOrFail($id);

        $video->update([
            'title' => $request->title ?? $video->title,
            'description' => $request->description ?? $video->description,
            'url' => $request->url ?? $video->url,
            'duration' => $request->duration ?? $video->duration
        ]);

        return response()->json($video);
    }

    // DELETAR VÍDEO
    public function destroy($id)
    {
        $video = Video::findOrFail($id);

        // apagar arquivo se existir
        if ($video->url && str_contains($video->url, 'storage/uploads')) {

            $path = str_replace('/storage/', '', $video->url);

            Storage::disk('public')->delete($path);

        }

        $video->delete();

        return response()->json([
            'message' => 'Vídeo deletado com sucesso'
        ]);
    }

    // UPLOAD DE VÍDEO (ADMIN)
    public function upload(Request $request)
{
    try {

        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime|max:102400',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $file = $request->file('video');

        // gerar nome único
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // salvar no storage
        $path = $file->storeAs('uploads', $fileName, 'public');

        // caminho físico do vídeo
        $videoPath = storage_path('app/public/' . $path);

        // pegar duração com FFProbe
        $ffprobe = FFProbe::create();

        $duration = $ffprobe
            ->format($videoPath)
            ->get('duration');

        // converter segundos para mm:ss
        $minutes = floor($duration / 60);
        $seconds = floor($duration % 60);
        $formattedDuration = $minutes . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);

        // url pública
        $url = '/storage/' . $path;

        // salvar no banco
        $video = Video::create([
            'title' => $request->title,
            'description' => $request->description ?? '',
            'url' => $url,
            'duration' => $formattedDuration,
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

}