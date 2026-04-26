<div class="p-4">
    <h2 class="text-xl font-bold mb-4">{{ $exerciseTitle }}</h2>

    <div class="aspect-w-16 aspect-h-9 bg-gray-100 rounded-lg overflow-hidden">
        <video
            src="{{ $videoUrl }}"
            controls
            class="w-full h-full object-contain"
            autoplay
        >
            Seu navegador não suporta a reprodução de vídeos.
        </video>
    </div>

    <div class="mt-4 text-sm text-gray-500">
        Clique fora desta janela para fechar o vídeo.
    </div>
</div>
