jQuery(document).ready(function ($) {
    let currentSongIndex = 0;
    let songs = [];
    let audio = new Audio();
    let isPlaying = false;
    let isDragging = false;

    $('.playlist_item').each(function () {
        songs.push($(this).data('src'));
    });

    function playSong(index) {
        if (index >= 0 && index < songs.length) {
            if (audio.src !== songs[index]) {
                audio.src = songs[index];
                audio.load();
            }
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
            if (audio.paused && audio.src) {
                audio.play();
                isPlaying = true;
                $('.play-pause').text('Pause');
            } else {
                playSong(currentSongIndex);
            }
        }
    }

    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        seconds = Math.floor(seconds % 60);
        const minutesStr = minutes < 10 ? '0' + minutes : minutes;
        const secondsStr = seconds < 10 ? '0' + seconds : seconds;
        return minutesStr + ':' + secondsStr;
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
        $('.volume-value').text(Math.round(audio.volume * 100));
    });

    audio.addEventListener('loadedmetadata', function () {
        // تنظیم مقدار حداکثری نوار پیشرفت به مدت زمان آهنگ
        $('.seekbar').attr('max', audio.duration);
        $('.duration-time').text(formatTime(audio.duration));
    });

    audio.addEventListener('timeupdate', function () {
        if (!isDragging) {
            $('.seekbar').val(audio.currentTime);
            $('.current-time').text(formatTime(audio.currentTime));
        }
    });

    $('.seekbar').on('input', function () {
        isDragging = true;
        // تغییر زمان فعلی بر اساس مقدار نوار پیشرفت
        audio.currentTime = $(this).val();
        $('.current-time').text(formatTime(audio.currentTime));
    });

    $('.seekbar').on('change', function () {
        isDragging = false;
    });

    audio.addEventListener('ended', function () {
        currentSongIndex = (currentSongIndex + 1) % songs.length;
        playSong(currentSongIndex);
    });
});
