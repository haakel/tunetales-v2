jQuery(document).ready(function () {
    let currentSongIndex = 0;
    let songs = [];
    let audio = new Audio();
    let isPlaying = false;
    let isDragging = false;
    let isShuffle = false;

    jQuery('.playlist_item').each(function () {
        songs.push(jQuery(this).data('src'));
    });

    function playSong(index) {
        if (index >= 0 && index < songs.length) {
            if (audio.src !== songs[index]) {
                audio.src = songs[index];
                audio.load();
            }
            audio.play();
            isPlaying = true;
            jQuery('.play-pause i').removeClass('fa-play').addClass('fa-pause');
        }
    }

    function pauseSong() {
        audio.pause();
        isPlaying = false;
        jQuery('.play-pause i').removeClass('fa-pause').addClass('fa-play');
    }

    function togglePlayPause() {
        if (isPlaying) {
            pauseSong();
        } else {
            if (audio.paused && audio.src) {
                audio.play();
                isPlaying = true;
                jQuery('.play-pause i').removeClass('fa-play').addClass('fa-pause');
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

    jQuery('.play-pause').on('click', function () {
        togglePlayPause();
    });

    jQuery('.playlist_item').on('click', function () {
        let index = jQuery(this).index();
        currentSongIndex = index;
        playSong(currentSongIndex);
    });

    jQuery('.next').on('click', function () {
        if (isShuffle && songs.length > 1) {
            currentSongIndex = getNextSongIndex(currentSongIndex);
        } else {
            currentSongIndex = (currentSongIndex + 1) % songs.length;
        }
        playSong(currentSongIndex);
    });

    jQuery('.prev').on('click', function () {
        if (isShuffle && songs.length > 1) {
            currentSongIndex = getNextSongIndex(currentSongIndex);
        } else {
            currentSongIndex = (currentSongIndex - 1 + songs.length) % songs.length;
        }
        playSong(currentSongIndex);
    });

    jQuery('.volume-slider').on('input', function () {
        let volumeValue = parseFloat(jQuery(this).val());
        audio.volume = volumeValue;
        jQuery('.volume-display').text(Math.round(volumeValue * 100));
    });

    audio.addEventListener('loadedmetadata', function () {
        jQuery('.seekbar').attr('max', audio.duration);
        jQuery('.duration-time').text(formatTime(audio.duration));
    });

    audio.addEventListener('timeupdate', function () {
        if (!isDragging) {
            jQuery('.seekbar').val(audio.currentTime);
            jQuery('.current-time').text(formatTime(audio.currentTime));
        }
    });

    jQuery('.seekbar').on('input', function () {
        isDragging = true;
        audio.currentTime = jQuery(this).val();
        jQuery('.current-time').text(formatTime(audio.currentTime));
    });

    jQuery('.seekbar').on('change', function () {
        isDragging = false;
    });

    jQuery('.shuffle').on('click', function () {
        if (songs.length > 1) {
            isShuffle = !isShuffle;
            jQuery(this).toggleClass('active');
            if (isShuffle) {
                currentSongIndex = getNextSongIndex(currentSongIndex);
                playSong(currentSongIndex);
            }
        } else {
            alert('Shuffle mode requires more than one song in the playlist.');
        }
    });

    audio.addEventListener('ended', function () {
        if (isShuffle && songs.length > 1) {
            currentSongIndex = getNextSongIndex(currentSongIndex);
        } else {
            currentSongIndex = (currentSongIndex + 1) % songs.length;
        }
        playSong(currentSongIndex);
    });
});
