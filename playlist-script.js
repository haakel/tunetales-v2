jQuery(document).ready(function ($) {
    let currentSongIndex = 0;
    let songs = [];
    let audio = new Audio();
    let isPlaying = false;

    $('.playlist_item').each(function () {
        songs.push($(this).data('src'));
    });

    function playSong(index) {
        if (index >= 0 && index < songs.length) {
            audio.src = songs[index];
            audio.play();
            isPlaying = true;
            $('.play-pause').text('Pause');
        }
    }

    function pauseSong() {
        audio.pause();
        isPlaying = false;
        $('.play-pause').text('Play');
    }

    function togglePlayPause() {
        if (isPlaying) {
            pauseSong();
        } else {
            playSong(currentSongIndex);
        }
    }

    $('.play-pause').on('click', function () {
        togglePlayPause();
    });

    $('.next').on('click', function () {
        currentSongIndex = (currentSongIndex + 1) % songs.length;
        playSong(currentSongIndex);
    });

    $('.prev').on('click', function () {
        currentSongIndex = (currentSongIndex - 1 + songs.length) % songs.length;
        playSong(currentSongIndex);
    });

    $('.volume').on('input', function () {
        audio.volume = $(this).val();
    });

    audio.addEventListener('timeupdate', function () {
        $('.seekbar').val(audio.currentTime / audio.duration);
    });

    $('.seekbar').on('input', function () {
        audio.currentTime = $(this).val() * audio.duration;
    });

    audio.addEventListener('ended', function () {
        currentSongIndex = (currentSongIndex + 1) % songs.length;
        playSong(currentSongIndex);
    });
});
