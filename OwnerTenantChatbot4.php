<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condominium Chatbot</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: linear-gradient(to right, #ff9800, #ffc107);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            font-size: 1.2em;
            height: 50px;
        }
        .header img {
            height: 30px;
        }
        .chatbot-container {
            width: 90%;
            max-width: 400px;
            height: 90%;
            max-height: 600px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        #chat-header {
            background-color: #007bff;
            color: #fff;
            padding: 10px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
        #chat {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .message {
            max-width: 75%;
            padding: 10px;
            border-radius: 20px;
            font-size: 14px;
            line-height: 1.5;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }
        .user-message {
            background-color: #007bff;
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
            border-bottom-left-radius: 20px;
        }
        .bot-message {
            background-color: #f0f0f0;
            align-self: flex-start;
            color: #333;
            border-bottom-right-radius: 20px;
            border-bottom-left-radius: 5px;
        }
        .message.show {
            opacity: 1;
            transform: translateY(0);
        }
        #prompts {
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .prompt {
            cursor: pointer;
            background-color: #007bff;
            color: #fff;
            padding: 8px 12px;
            border-radius: 15px;
            text-align: center;
            font-size: 14px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .prompt:hover {
            background-color: #005bb5;
        }
        .prompt:active {
            transform: scale(0.98);
        }
        .loading-dots span {
            display: inline-block;
            font-size: 18px;
            line-height: 18px;
            animation: blink 1.4s infinite both;
        }
        .loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }
        .loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }
        @keyframes blink {
            0% { opacity: 0.2; }
            20% { opacity: 1; }
            100% { opacity: 0.2; }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div id="header">CondoBot</div>
        <div id="chat">
            <!-- Messages will appear here -->
        </div>
        <div id="prompts">
            <div class="prompt" onclick="sendPrompt('owner1')">I forgot my password</div>
            <div class="prompt" onclick="sendPrompt('owner2')">What if I tried multiple times with incorrect credential</div>
            <div class="prompt" onclick="sendPrompt('finance1')">After login for the first time, why there is an alertbox</div>
        </div>
    </div>

    <script>
        const responses = {
            'owner1': 'Click forgot password give your email and type your new password.',
            'owner2': 'Textbox in login will lock 20 to 30 seconds you cant login right away. Forgot password will forcely appear if tried more than 5 times for you to change your credentials',
            'finance1': 'Your account status is unverified which means you can only access limited tabs in the homepage. Wait for the admin to either approve or disapprove your account',
        };

        function sendPrompt(prompt) {
            const chat = document.getElementById('chat');

            // User's message
            const userMessage = document.createElement('div');
            userMessage.classList.add('message', 'user-message');
            userMessage.innerText = document.querySelector(`[onclick="sendPrompt('${prompt}')"]`).innerText;
            chat.appendChild(userMessage);

            // Bot loading message
            const loadingMessage = document.createElement('div');
            loadingMessage.classList.add('message', 'bot-message');
            loadingMessage.innerHTML = '<span class="loading-dots"><span>.</span><span>.</span><span>.</span></span>';
            chat.appendChild(loadingMessage);

            // Trigger animation after appending user message
            setTimeout(() => {
                userMessage.classList.add('show');
                loadingMessage.classList.add('show');
                chat.scrollTop = chat.scrollHeight; // Scroll to bottom
            }, 50);

            // Replace loading with response after delay
            setTimeout(() => {
                loadingMessage.innerText = responses[prompt];
                loadingMessage.classList.add('show');
                chat.scrollTop = chat.scrollHeight;
            }, 2000); // Delay for showing loading dots
        }
    </script>
</body>
</html>
