window.onload = function () {
    const ui = SwaggerUIBundle({
      url: "/api/docs",
      dom_id: "#swagger-ui",
      layout: "StandaloneLayout",
      deepLinking: true,
      filter: true,
      docExpansion: "list",
      defaultModelsExpandDepth: 0,
      displayRequestDuration: true,
      tryItOutEnabled: true,
      presets: [
        SwaggerUIBundle.presets.apis,
        SwaggerUIStandalonePreset
      ]
    });
  
    window.ui = ui;
  
    // THEME TOGGLE
    const btn = document.getElementById("theme-toggle");
    btn.onclick = () => {
      const dark = document.body.style.background === "white";
      document.body.style.background = dark ? "#0d1117" : "white";
      btn.innerText = dark ? "🌙 Dark Mode" : "☀️ Light Mode";
    };
  };
  