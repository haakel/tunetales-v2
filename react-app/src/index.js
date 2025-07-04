import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";

// Recherche de l'élément racine dans le DOM.
// Cet ID doit correspondre à celui que nous allons définir dans notre template PHP.
const rootElement = document.getElementById("tunetales-react-player-root");

if (rootElement) {
  const root = ReactDOM.createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
} else {
  console.error(
    "TuneTales React Player: Root element #tunetales-react-player-root not found."
  );
}
