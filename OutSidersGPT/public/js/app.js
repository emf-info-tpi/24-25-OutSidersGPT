// Initialise l'historique de la conversation avec un message système
let conversationHistory = [
  {
    role: "system",
    content: "Tu es un assistant utile. Réponds clairement et poliment."
  }
];

/**
 * Ajoute un message (utilisateur ou assistant) dans le DOM.
 * 
 * @param {"user"|"assistant"} role - Le rôle de l'expéditeur.
 * @param {string} text - Le contenu du message.
 */
function appendMessage(role, text) {
  const chatBox = document.getElementById("chat");
  const bubble = document.createElement("div");

  bubble.classList.add("message");
  if (role === "user") {
    bubble.classList.add("user-message");// Style utilisateur
  } else {
    bubble.classList.add("ai-message");// Style assistant
  }

  bubble.textContent = text;
  chatBox.appendChild(bubble);
  chatBox.scrollTop = chatBox.scrollHeight;// Scroll vers le bas
}

/**
 * Envoie une requête à l'API GPT avec l'historique de la conversation.
 * Affiche la réponse dans le chat et l'ajoute à l'historique.
 */
async function sendPrompt() {
  const promptInput = document.getElementById("prompt");
  const prompt = promptInput.value.trim();
  if (!prompt) return;

  promptInput.value = "";
  appendMessage("user", prompt);// Affiche le message utilisateur

  // Ajoute à l'historique de la conversation
  conversationHistory.push({
    role: "user",
    content: prompt
  });

  try {
    
    const res = await fetch("http://localhost/OutsidersGPT/api/gpt4all.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        model: "Llama 3.2 1B Instruct",
        messages: conversationHistory,
        max_tokens: 2000,
        temperature: 0.7,
        top_p: 0.95
      })
    });

    // Affiche le texte brut de la réponse
    const text = await res.text();
    console.log("Réponse brute reçue :", text);

    let data;
    try {
      data = JSON.parse(text);
    } catch (parseError) {
      appendMessage("assistant", "❌ Erreur : réponse non valide JSON\n" + text.slice(0, 200));
      console.error("Erreur de parsing JSON :", parseError);
      return;
    }

    // Extrait le contenu généré
    const reply = data?.choices?.[0]?.message?.content?.trim() || "(réponse vide)";
    appendMessage("assistant", reply);

    // Ajoute la réponse à l'historique
    conversationHistory.push({
      role: "assistant",
      content: reply
    });
  } catch (e) {
    console.error("Erreur API :", e);
    appendMessage("assistant", "❌ Erreur : " + e.message);
  }
}

/**
 * Lance une nouvelle discussion :
 * - Vide l'historique côté client.
 * - Crée une nouvelle discussion côté serveur.
 * - Ajoute dynamiquement un lien dans la liste des discussions.
 */
function startNewConversation() {
  fetch("http://localhost/OutsidersGPT/api/new_discussion.php", { method: "GET" })

    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Réinitialise l'historique côté client
        conversationHistory = [
          {
            role: "system",
            content: "Tu es un assistant utile. Réponds clairement et poliment."
          }
        ];
        document.getElementById("chat").innerHTML = "";
        document.getElementById("prompt").value = "";

        // ajoute dynamiquement le lien dans la barre latérale
        const discussionsContainer = document.querySelector(".discussions");
        const newLink = document.createElement("a");
        newLink.href = "#";
        newLink.textContent = data.nom;
        discussionsContainer.appendChild(newLink);
        loadDiscussions(); // Recharge la liste
      } else {
        console.error(data.message);
      }
    })
    .catch((err) => {
      console.error("Erreur lors de la création de la discussion :", err);
    });
}

/**
 * Charge la liste des discussions depuis le serveur et les affiche dans l'interface.
 * 
 * - Effectue une requête HTTP vers l'API `get_discussions.php`.
 * - Vide le conteneur `.discussions`.
 * - Pour chaque discussion, crée un lien cliquable avec son nom.
 * - Lors d'un clic, appelle `loadMessagesForDiscussion` avec l'ID correspondant.
 */
function loadDiscussions() {
  fetch("http://localhost/OutsidersGPT/api/get_discussions.php")
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Erreur récupération discussions :", data.message);
        return;
      }

      const container = document.querySelector(".discussions");
      container.innerHTML = ""; // vide l'existant

      data.discussions.forEach(disc => {
        const a = document.createElement("a");
        a.href = "#";
        a.textContent = disc.Nom;
        a.dataset.id = disc.PK_Discussion;
      
        // Lorsqu'on clique, on charge les messages
        a.addEventListener("click", () => {
          loadMessagesForDiscussion(disc.PK_Discussion);
        });
      
        container.appendChild(a);
      });
      
    })
    .catch(err => {
      console.error("Erreur chargement discussions :", err);
    });
}

/**
 * Charge les messages pour une discussion donnée par son ID.
 * Met à jour l'interface et reconstruit l'historique local.
 * 
 * @param {number} id - L'identifiant de la discussion.
 */
function loadMessagesForDiscussion(id) {
  fetch(`http://localhost/OutsidersGPT/api/load_messages.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Erreur récupération messages :", data.message);
        return;
      }

      // Réinitialise l'affichage
      document.getElementById("chat").innerHTML = "";
      conversationHistory = [
        {
          role: "system",
          content: "Tu es un assistant utile. Réponds clairement et poliment."
        }
      ];

      data.messages.forEach(msg => {
        const role = msg.GPT == 1 ? "assistant" : "user";

        appendMessage(role, msg.Message);
        conversationHistory.push({
          role,
          content: msg.Message
        });
      });
    })
    .catch(err => {
      console.error("Erreur chargement messages :", err);
    });
}

// Popup : clique sur "Nouveau GPT"
document.querySelector(".new-gpt").addEventListener("click", () => {
  console.log("Nouveau GPT cliqué");

  document.getElementById("gptModal").style.display = "flex";
});

// Annule et ferme la popup
document.getElementById("cancelGptBtn").addEventListener("click", () => {
  document.getElementById("gptModal").style.display = "none";
  document.getElementById("gptNameInput").value = "";
});

// enregistrement
document.getElementById("saveGptBtn").addEventListener("click", () => {
  const nom = document.getElementById("gptNameInput").value.trim();
  const systemMsg = document.getElementById("gptSystemInput").value.trim();

  if (!nom) {
    alert("Veuillez entrer un nom pour le GPT.");
    return;
  }

  fetch("http://localhost/OutsidersGPT/api/save_gpt.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      nom: nom,
      prompt: systemMsg
    })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(`GPT "${nom}" enregistré avec l’ID ${data.id}`);
        document.getElementById("gptModal").style.display = "none";
        document.getElementById("gptNameInput").value = "";
        document.getElementById("gptSystemInput").value = "";
      } else {
        alert("Erreur lors de la sauvegarde : " + data.message);
      }
    })
    .catch(err => {
      console.error("Erreur réseau :", err);
      alert("Erreur lors de la requête.");
    });
});

/**
 * Charge les GPT personnalisés depuis le serveur et les affiche dans l'interface utilisateur.
 * 
 * - Envoie une requête à l'API `get_gpts.php`.
 * - Si la réponse est réussie :
 *    - Vide la section `.bottom-section`.
 *    - Reconstruit dynamiquement la liste des GPT avec des éléments <a>.
 *    - Chaque lien déclenche `loadGptAsSystemPrompt()` au clic, pour charger le prompt correspondant.
 * - Réinsère le bouton `.new-gpt` à la fin de la section.
 */
function loadCustomGpts() {
  fetch("http://localhost/OutsidersGPT/api/get_gpts.php")
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Erreur récupération GPT :", data.message);
        return;
      }

      const section = document.querySelector(".bottom-section");
      const button = section.querySelector(".new-gpt");
      section.innerHTML = ""; // vide tout

      const gptContainer = document.createElement("div");
      gptContainer.classList.add("gpt-list");

      data.gpts.forEach(gpt => {
        const a = document.createElement("a");
        a.href = "#";
        a.textContent = gpt.Nom;
        a.dataset.id = gpt.PK_GPT;

        // Clique pour charger le prompt
        a.addEventListener("click", () => {
          loadGptAsSystemPrompt(gpt.PK_GPT, gpt.Nom);
        });

        gptContainer.appendChild(a);
      });

      section.appendChild(gptContainer);
      section.appendChild(button);
    })
    .catch(err => {
      console.error("Erreur chargement GPT personnalisés :", err);
    });
}

/**
 * Charge le prompt système d’un GPT personnalisé à partir de son ID,
 * puis initialise une nouvelle discussion temporaire avec ce prompt.
 *
 * @param {number} id - L'identifiant du GPT à charger.
 * @param {string} name - Le nom du GPT, utilisé pour afficher une info-bulle.
 *
 * - Envoie une requête à `get_gpt_prompt.php` avec l'ID.
 * - Si la récupération est un succès :
 *    - Réinitialise l’historique de conversation avec le prompt reçu.
 *    - Vide l’affichage du chat.
 *    - Affiche une note indiquant que la session est temporaire.
 */
function loadGptAsSystemPrompt(id, name) {
  fetch(`http://localhost/OutsidersGPT/api/get_gpt_prompt.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Erreur chargement du prompt :", data.message);
        return;
      }

      // Réinitialise l'historique avec le prompt personnalisé
      conversationHistory = [
        {
          role: "system",
          content: data.prompt || "Tu es un assistant utile."
        }
      ];

      // Vide l'affichage
      document.getElementById("chat").innerHTML = "";
      document.getElementById("prompt").value = "";

      // Affiche un message informatif
      const chatBox = document.getElementById("chat");
      const notice = document.createElement("div");
      notice.classList.add("ai-message");
      notice.textContent = `💡 Discussion temporaire avec "${name}"`;
      chatBox.appendChild(notice);
    })
    .catch(err => {
      console.error("Erreur GPT personnalisé :", err);
    });
}


// Au chargement de la page, initialise discussions et GPTs
window.addEventListener("DOMContentLoaded", () => {
  loadDiscussions();
  loadCustomGpts();
});
