console.log(`window.jwpSocketParams`, window.jwpSocketParams)
console.log(`window.jwpChannelParams`, window.jwpChannelParams)
console.log(`window.jwpChannelName`, window.jwpChannelName)
jwp.enableDebug()
var room = window.jwpChannelName
var socket = jwp.connect('ws://localhost:4000/socket', window.jwpSocketParams)

socket.onMessage(function (msg) {
  if (msg.event === 'jwp_system' && msg.payload.type === 'error') {
    console.error('jwp system error: ', msg.payload.message)
  } else {
    console.log(`msg`, msg)
  }
})

var channel = socket.channel(room, window.jwpChannelParams)


channel.join()
  .receive('error', function (err) {
    console.error(err)
  })
  .receive('ok', _ => sendMessage("hello"))

console.log(`called join`)

var bodyInput = document.getElementById('msg-body')
var sendButton = document.getElementById('msg-send')
var messagesList = document.getElementById('messages-list')

channel.on('chat_msg', function (data) {
  var p = document.createElement('p')
  p.innerText = data.message
  messagesList.appendChild(p)
})

function sendInput() {
  var msg = bodyInput.value
  bodyInput.value = ''
  sendMessage(msg)
}

function sendMessage(msg) {
  var headers = new Headers();
  headers.append('content-type', 'application/json');
  fetch('/send/' + window.jwpChannelName, {
    method: 'POST',
    headers: headers,
    body: JSON.stringify({ message: msg }),
  })
    .then(function (response) { return response.json() })
    .then(function (data) { console.log(`data`, data) })
    .catch(err => { console.error(err) })
}

bodyInput.addEventListener('keypress', function (evt) {
  if (evt.key === 'Enter' || evt.code === 'Enter' || evt.which === 13 || evt.which === 10) {
    sendInput()
  }
})

sendButton.addEventListener('click', sendInput)

// var presence = channel.presence()

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
  Object.keys(payload.leaves)
    .map(function (k) {
      return payload.leaves[k].metas[0].username
    })
    .map(function (username) {
      var p = document.createElement('p')
      p.style.color = '#aaa'
      p.innerText = username + ' a quitté le chat !'
      messagesList.appendChild(p)
    })
})

// var pingCount = 0
// function sendLoop() {
//   return requestAnimationFrame(function(){
//     console.log(`pingCount`, pingCount)
//     sendMessage("ping-" + (++pingCount)) 
//     if (pingCount < 10) {
//       sendLoop()
//     }
//   })
// }
// sendLoop()


try { bodyInput.focus() } catch (_) { }