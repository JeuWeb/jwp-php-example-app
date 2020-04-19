console.log(`window.jwpParams`, window.jwpParams)
var socket = jwp.connect(window.jwpParams)

socket.onMessage(function (msg) {
  if (msg.event === 'jwp_system' && msg.payload.type === 'error') {
    console.error('jwp system error: ', msg.payload.message)
  }
})

var channel = socket.channel('general')
channel.join()

var bodyInput = document.getElementById('msg-body')
var sendButton = document.getElementById('msg-send')
var messagesList = document.getElementById('messages-list')

channel.on('chat_msg', function (data) {
  var p = document.createElement('p')
  p.innerText = data.message
  messagesList.appendChild(p)
})

function sendMessage() {
  var msg = bodyInput.value
  bodyInput.value = ''
  var headers = new Headers();
  headers.append('content-type', 'application/json');
  fetch('/chat', {
    method: 'POST',
    headers: headers,
    body: JSON.stringify({ message: msg }),
  })
    .then(function (response) { return response.json() })
    .then(function (data) { console.log(`data`, data) })
}

bodyInput.addEventListener('keypress', function (evt) {
  if (evt.key === 'Enter' || evt.code === 'Enter' || evt.which === 13 || evt.which === 10) {
    sendMessage()
  }
})

sendButton.addEventListener('click', sendMessage)

var presence = channel.presence()

channel.on('presence_diff', function (payload) {
  Object.keys(payload.joins)
    .map(function (k) {
      return payload.joins[k].metas[0].username
    })
    .map(function (username) {
      var p = document.createElement('p')
      p.style.color = '#aaa'
      p.innerText = username + ' a rejoint le chat !'
      messagesList.appendChild(p)
    })
})



try { bodyInput.focus() } catch (_) { }