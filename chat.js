document.addEventListener('DOMContentLoaded', () => {
    // Create the thread when the page loads
    fetch('create_thread.php')
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          alert(`Error: ${data.error}`);
        } else {
          localStorage.setItem('threadId', data.id);
        }
      })
      .catch(error => {
        alert(`Error: ${error.message}`);
      });
  
    document.getElementById('sendMessage').addEventListener('click', () => {
      const threadId = localStorage.getItem('threadId');
      const message = document.getElementById('message').value;
  
      if (!threadId || !message) {
        alert('Thread ID and message are required.');
        return;
      }
  
      appendMessage('user', message);
  
      // Send the message to the assistant
      fetch('create_message.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ threadId, message })
      })
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            appendMessage('assistant', `Error: ${data.error}`);
            return;
          }
  
          // Create a run to get the streamed response
          fetch('create_run.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            //your assistant ID will be used below. 
            body: JSON.stringify({ threadId, assistantId: '' })
          })
          .then(runResponse => {
            if (!runResponse.ok) {
              throw new Error('Network response was not ok: ' + runResponse.statusText);
            }
            const reader = runResponse.body.getReader();
            const decoder = new TextDecoder();
            let accumulatedText = '';
            let result = '';
            let assistantMessageElement = appendMessage('assistant', '');
  
            function readStream() {
              reader.read().then(({ done, value }) => {
                if (done) {
                  return;
                }
                result += decoder.decode(value, { stream: true });
                const lines = result.split('\n');
                result = lines.pop(); // Save incomplete line
                lines.forEach(line => {
                  if (line.startsWith('data: ')) {
                    const text = line.slice(6); // Remove 'data: '
                    if (text !== '[DONE]') {
                      accumulatedText += text;
                      assistantMessageElement.innerText = accumulatedText; // Update message text without adding new lines
                    }
                  }
                });
                readStream();
              }).catch(error => {
                appendMessage('assistant', `Error reading stream: ${error.message}`);
              });
            }
            readStream();
          })
          .catch(error => {
            appendMessage('assistant', `Error: ${error.message}`);
          });
        })
        .catch(error => {
          appendMessage('assistant', `Error: ${error.message}`);
        });
    });
  
    function appendMessage(sender, message) {
      const chatBox = document.getElementById('chatBox');
      const messageElement = document.createElement('div');
      messageElement.className = `chat-message ${sender}`;
      messageElement.innerText = message;
      chatBox.appendChild(messageElement);
      chatBox.scrollTop = chatBox.scrollHeight; // Auto-scroll to the latest message
      return messageElement;
    }
  });
  