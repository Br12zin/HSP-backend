<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

class VideoController extends Controller
{

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