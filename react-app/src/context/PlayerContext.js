import React, { createContext, useState, useContext, useEffect } from "react";

const PlayerContext = createContext();

export const usePlayer = () => {
  return useContext(PlayerContext);
};

export const PlayerProvider = ({ children }) => {
  const [songs, setSongs] = useState([]);
  const [currentSongIndex, setCurrentSongIndex] = useState(null); // Null signifie qu'aucune chanson n'est sélectionnée/chargée
  const [isPlaying, setIsPlaying] = useState(false);
  const [volume, setVolume] = useState(1); // de 0 à 1
  const [currentTime, setCurrentTime] = useState(0); // en secondes
  const [duration, setDuration] = useState(0); // en secondes
  const [repeatMode, setRepeatMode] = useState(0); // 0: pas de répétition, 1: répétition de la chanson, 2: répétition de la playlist
  const [isShuffle, setIsShuffle] = useState(false);
  const [currentPlaylistInfo, setCurrentPlaylistInfo] = useState(null); // Infos sur la playlist actuelle (si applicable)
  const [allPlaylists, setAllPlaylists] = useState([]); // Pour la page d'archive
  const [isArchivePage, setIsArchivePage] = useState(false);

  // Référence à l'élément audio
  const audioRef = React.useRef(null);

  // Effet pour charger les données initiales de WordPress
  useEffect(() => {
    if (window.tunetalesReact) {
      const {
        songs: initialSongs = [],
        current_playlist_info: initialPlaylistInfo = null,
        playlists: initialAllPlaylists = [],
        is_archive_page: initialIsArchivePage = false,
      } = window.tunetalesReact;

      setSongs(initialSongs);
      setCurrentPlaylistInfo(initialPlaylistInfo);
      setAllPlaylists(initialAllPlaylists);
      setIsArchivePage(initialIsArchivePage);

      if (initialSongs.length > 0) {
        setCurrentSongIndex(0); // Sélectionner la première chanson par défaut
      }
    }
  }, []);

  // TODO: Ajouter ici les fonctions pour contrôler la lecture (play, pause, next, prev, setVolume, seek, etc.)
  // Ces fonctions mettront à jour l'état et interagiront avec audioRef.current

  const playSong = (index) => {
    if (index >= 0 && index < songs.length) {
      setCurrentSongIndex(index);
      setIsPlaying(true);
      // La logique de chargement et de lecture de audioRef sera ajoutée plus tard
    }
  };

  const togglePlayPause = () => {
    setIsPlaying(!isPlaying);
    // La logique de lecture/pause de audioRef sera ajoutée plus tard
  };

  // Plus de fonctions de contrôle ici...

  const value = {
    songs,
    setSongs,
    currentSongIndex,
    setCurrentSongIndex,
    isPlaying,
    setIsPlaying,
    volume,
    setVolume,
    currentTime,
    setCurrentTime,
    duration,
    setDuration,
    repeatMode,
    setRepeatMode,
    isShuffle,
    setIsShuffle,
    currentPlaylistInfo,
    allPlaylists,
    isArchivePage,
    audioRef, // Exposer la référence audio pour que les composants puissent l'utiliser si nécessaire
    // Fonctions de contrôle
    playSong,
    togglePlayPause,
    // ... autres fonctions
  };

  return (
    <PlayerContext.Provider value={value}>
      {children}
      <audio ref={audioRef} />
      {/* L'élément audio réel, il peut être stylé pour être caché si l'interface est entièrement personnalisée */}
    </PlayerContext.Provider>
  );
};
