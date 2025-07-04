const path = require("path");

module.exports = (env, argv) => {
  const isDevelopment = argv.mode === "development";

  return {
    mode: isDevelopment ? "development" : "production",
    entry: "./src/index.js",
    output: {
      path: path.resolve(__dirname, "dist"),
      filename: "bundle.js",
      publicPath:
        "/wp-content/plugins/tunetales-music-playlist/react-app/dist/", // Ajustez si nécessaire
    },
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          use: {
            loader: "babel-loader",
            options: {
              presets: ["@babel/preset-env", "@babel/preset-react"],
            },
          },
        },
        {
          test: /\.css$/,
          use: [
            "style-loader",
            {
              loader: "css-loader",
              options: {
                modules: {
                  localIdentName: isDevelopment
                    ? "[name]__[local]--[hash:base64:5]"
                    : "[hash:base64:8]",
                },
                importLoaders: 1,
              },
            },
          ],
        },
        {
          test: /\.(png|svg|jpg|jpeg|gif)$/i,
          type: "asset/resource",
        },
      ],
    },
    resolve: {
      extensions: [".js", ".jsx"],
    },
    devtool: isDevelopment ? "eval-source-map" : "source-map", // Source maps pour le débogage
    // externals: { // Si vous prévoyez d'utiliser React et ReactDOM depuis WordPress (non recommandé pour les blocs)
    //   'react': 'React',
    //   'react-dom': 'ReactDOM'
    // }
  };
};
