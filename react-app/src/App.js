import React from "react";
import styles from "./App.module.css"; // Importation du module CSS
import { PlayerProvider, usePlayer } from "./context/PlayerContext";

// Un composant de test pour afficher certaines informations du contexte
const DebugPlayerInfo = () => {
  const {
    songs,
    currentSongIndex,
    isPlaying,
    currentPlaylistInfo,
    isArchivePage,
    allPlaylists,
  } = usePlayer();
  const currentSong =
    songs && currentSongIndex !== null && songs[currentSongIndex]
      ? songs[currentSongIndex]
      : null;

  if (isArchivePage) {
    return (
      <div>
        <h3>Mode Archive</h3>
        <p>Nombre de playlists : {allPlaylists.length}</p>
        {allPlaylists.length > 0 && (
          <ul>
            {allPlaylists.map((playlist) => (
              <li key={playlist.id}>
                {playlist.title} ({playlist.song_count} chansons)
              </li>
            ))}
          </ul>
        )}
      </div>
    );
  }

  return (
    <div>
      {currentPlaylistInfo && <h4>Playlist : {currentPlaylistInfo.title}</h4>}
      <p>Nombre de chansons : {songs.length}</p>
      {currentSong ? (
        <p>
          Chanson actuelle : {currentSong.title} (Index: {currentSongIndex})
        </p>
      ) : (
        <p>Aucune chanson sélectionnée.</p>
      )}
      <p>En lecture : {isPlaying ? "Oui" : "Non"}</p>
    </div>
  );
};

function AppContent() {
  return (
    <div className={styles.appContainer}>
      <h1 className={styles.title}>TuneTales React Player</h1>
      <p>
        Le lecteur de musique TuneTales est en cours de construction avec React.
      </p>
      <hr />
      <DebugPlayerInfo />
      {/* Les composants SongInfo, PlayerControls, PlaylistDisplay seront ajoutés ici plus tard */}
    </div>
  );
}

function App() {
  return (
    <PlayerProvider>
      <AppContent />
    </PlayerProvider>
  );
}

export default App;
