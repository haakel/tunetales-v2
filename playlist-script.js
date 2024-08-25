jQuery(document).ready(function ($) {
    let currentSongIndex = 0;
    let songs = [];
    let audio = new Audio();
    let isPlaying = false;
    let isDragging = false;
    let isShuffle = false;

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
            $('.play-pause i').removeClass('fa-play').addClass('fa-pause');
        }
    }

    function pauseSong() {
        audio.pause();
        isPlaying = false;
        $('.play-pause i').removeClass('fa-pause').addClass('fa-play');
    }

    function togglePlayPause() {
        if (isPlaying) {
            pauseSong();
        } else {
            if (audio.paused && audio.src) {
                audio.play();
                isPlaying = true;
                $('.play-pause i').removeClass('fa-play').addClass('fa-pause');
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

    function getNextSongIndex(currentIndex) {
        let nextIndex;
        do {
            nextIndex = Math.floor(Math.random() * songs.length);
        } while (nextIndex === currentIndex);
        return nextIndex;
    }

    $('.play-pause').on('click', function () {
        togglePlayPause();
    });

    $('.playlist_item').on('click', function () {
        let index = $(this).index();
        currentSongIndex = index;
        playSong(currentSongIndex);
    });

    $('.next').on('click', function () {
        if (isShuffle) {
            currentSongIndex = getNextSongIndex(currentSongIndex);
        } else {
            currentSongIndex = (currentSongIndex + 1) % songs.length;
        }
        playSong(currentSongIndex);
    });

    $('.prev').on('click', function () {
        if (isShuffle) {
            currentSongIndex = getNextSongIndex(currentSongIndex);
        } else {
            currentSongIndex = (currentSongIndex - 1 + songs.length) % songs.length;
        }
        playSong(currentSongIndex);
    });

$('.volume-slider').on('input', function () {
    let volumeValue = parseFloat($(this).val());

    // Set the volume for the audio element
    audio.volume = volumeValue;

    // Display the volume percentage
    $('.volume-display').text(Math.round(volumeValue * 100));
});


    

    audio.addEventListener('loadedmetadata', function () {
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
        audio.currentTime = $(this).val();
        $('.current-time').text(formatTime(audio.currentTime));
    });

    $('.seekbar').on('change', function () {
        isDragging = false;
    });

    $('.shuffle').on('click', function () {
        isShuffle = !isShuffle;
        $(this).toggleClass('active');
        if (isShuffle) {
            currentSongIndex = getNextSongIndex(currentSongIndex);
            playSong(currentSongIndex); // اگر در حالت شافل قرار گرفت، آهنگ تصادفی را پخش کنید
        }
    });

    audio.addEventListener('ended', function () {
        if (isShuffle) {
            currentSongIndex = getNextSongIndex(currentSongIndex);
        } else {
            currentSongIndex = (currentSongIndex + 1) % songs.length;
        }
        playSong(currentSongIndex);
    });
});
