// Require the packages we will use:
const http = require("http"),
    fs = require("fs");

const port = 3456;
const file = "client.html";

const server = http.createServer(function (req, res) {

    fs.readFile(file, function (err, data) {

        if (err) return res.writeHead(500);
        res.writeHead(200);
        res.end(data);
    });
});
server.listen(port);


const socketio = require("socket.io")(http, {
    wsEngine: 'ws'
});

let rooms = [];
let user_list = [];
let sid = {};

function create_chatroom(name, pass, admin){
    let room = {
        'room_name': name,
        'room_pass': pass,
        'room_admin': admin,
        'room_coadmin': [],
        'users': [],
        'room_muted': [],
        'room_banned': []
    }
    rooms.push(room);
}

const io = socketio.listen(server);
io.sockets.on("connection", function (socket) {
    // send message to client (public)
    socket.on('message_to_server', function (data) {
        const room = rooms.find(r => r.room_name === data['room']);
        if (room){
            if (room.room_muted.includes(data['name'])){
                io.to(data['room']).emit('message_toClient', {result: 0, message:"muted", nickname:data['name']})
            }
            else{
                console.log("message: " + data["message"]);
                console.log("room: ", data['room']);
                io.to(data['room']).emit('message_toClient', {result: 1, message:data['message'], nickname:data['name']})
            }
        }
        
    });

    // send message to client (private)
    socket.on('private_message_to_server', function (data) {
        
        const room = rooms.find(r => r.room_name === data['room']);
        if (room){
            if (room.room_muted.includes(data['name'])){
                io.to(data['room']).emit('message_toClient', {result: 0, message:"muted", nickname:data['name']})
            }
            else{
                console.log("message: " + data["message"]);
                console.log("room: ", data['room']);
                console.log("receiver: ", data['receiver']);
                io.to(data['room']).emit('message_toClient', {result: 1, message:data['message'], nickname:data['name'], receiver: data['receiver']})
            }
        }
    });
    
    // register new user
    socket.on('new_user', function(data){
        console.log('new user: ', data['nickname_']);
        let valid_name = 1;
        // check name exist
        for (const user_inlist of user_list){
            if (data['nickname_'] == user_inlist){
                valid_name = 0;
                io.emit('new_user_result', {result: 0, nickname: data['nickname_']});
            }
            break;
        }
        if (valid_name){
            user_list.push(data['nickname_']);
            sid[socket.id] = data['nickname_'];
            io.emit('new_user_result', {result: 1, nickname: data['nickname_']});
        }
        
    })

    // kick user
    socket.on('kick_user', function(data){
        console.log("kick: ", data['kicked_user']);
        const room = rooms.find(r => r.room_name === data['room']);
        if (room) {
            room.users = room.users.filter(user => user !== data['kicked_user']);
            io.to(data['room']).emit('user_kicked', { kicked_user: data['kicked_user'], room: data['room'] });
            let targetID;
            // get socket from socket id
            for (const socketID in sid){
                if (sid[socketID] == data['kicked_user']){
                    targetID = socketID;
                }
            }
            let targetSocket = io.sockets.sockets.get(targetID);
            targetSocket.leave(data['room']); 
        }
    });

    // quit room
    socket.on('quit_room', function(data){
        console.log("quit: ", data['quit_user']);
        const room = rooms.find(r => r.room_name === data['room']);
        if (room) {
            room.users = room.users.filter(user => user !== data['quit_user']);
            io.to(data['room']).emit('user_quit', { quit_user: data['quit_user'], room: data['room'] });
            socket.leave(data['room']); 
        }
    });

    // ban user
    socket.on('ban_user', function(data){
        console.log("ban: ", data['banned_user']);
        const room = rooms.find(r => r.room_name === data['room']);
        if (room) {
            room.room_banned.push(data['banned_user']);
            room.users = room.users.filter(user => user !== data['banned_user']);
            io.to(data['room']).emit('user_banned', { banned_user: data['banned_user'], room: data['room']});
            let targetID;
            for (const socketID in sid){
                if (sid[socketID] == data['banned_user']){
                    targetID = socketID;
                }
            }
            let targetSocket = io.sockets.sockets.get(targetID);
            targetSocket.leave(data['room']); 
        }
    });

    // mute the users (creative)
    socket.on('mute_user', function(data){
        console.log("mute: ", data['muted_user']);
        const room = rooms.find(r => r.room_name === data['room']);
        if (room) {
            room.room_muted.push(data['muted_user']);
            io.to(data['room']).emit('user_muted', { muted_user: data['muted_user'], room: data['room']});
        }
    });

    // unmute users
    socket.on('unmute_user', function(data){
        console.log("unmute: ", data['unmuted_user']);
        const room = rooms.find(r => r.room_name === data['room']);
        if (room) {
            room.room_muted = room.room_muted.filter(user => user !== data['unmuted_user']);
            io.to(data['room']).emit('user_unmuted', { unmuted_user: data['unmuted_user'], room: data['room']});
        }
    });

    // assign co-admin (creative)
    socket.on('assign_user', function(data){
        console.log("assign: ", data['assigned_user']);
        const room = rooms.find(r => r.room_name === data['room']);
        if (room) {
            room.room_coadmin.push(data['assigned_user']);
            io.to(data['room']).emit('user_assigned', { assigned_user: data['assigned_user'], room: data['room']});
        }
    });

    // remove co-admin (creative)
    socket.on('unassign_user', function(data){
        console.log("unassign: ", data['unassigned_user']);
        const room = rooms.find(r => r.room_name === data['room']);
        if (room) {
            room.room_coadmin = room.room_coadmin.filter(user => user !== data['unassigned_user']);
            io.to(data['room']).emit('user_unassigned', { unmuted_user: data['unassigned_user'], room: data['room']});
        }
    });

    // create public room
    socket.on('create_publicroom_request', function (data){
        console.log("room name: ", data['roomname']);
        console.log("room pass: ", null);
        console.log("room admin: ", data['admin']);
        let valid_name = 1;
        for (const room of rooms){
            if (data['roomname'] == room.room_name){
                valid_name = 0;
                io.emit('create_publicroom_result', {allroom: rooms, result: valid_name, nickname: data['nickname_']});
            }
        }
        if (valid_name){
            create_chatroom(data['roomname'], null, data['admin']);
            io.emit('create_publicroom_result', {allroom: rooms, result: valid_name, nickname: data['nickname_']});
        }
    });

    // create private room
    socket.on('create_privateroom_request', function (data){
        console.log("room name: ", data['roomname']);
        console.log("room pass: ", data['roompass']);
        console.log("room admin: ", data['admin']);
        let valid_name = 1;
        for (const room of rooms){
            if (data['roomname'] == room.room_name){
                valid_name = 0;
                io.emit('create_privateroom_result', {allroom: rooms, result: valid_name, nickname: data['nickname_']});
            }
        }
        if (valid_name){
            create_chatroom(data['roomname'], data['roompass'], data['admin']);
            io.emit('create_privateroom_result', {allroom: rooms, result: valid_name, nickname: data['nickname_']});
        }
    });

    // get all rooms
    socket.on('get_room_request', function(data){
        io.emit('all_rooms', {room: rooms});
    });

    // address the join room request
    socket.on('join_room_request', function (data) {
        console.log("room: ", data['roomname']);
        console.log("nickname: ", data['nickname_']);
        const room = rooms.find(r => r.room_name === data['roomname']);
        
        if (room) {
            if (room.room_banned.includes(data['nickname_'])) {
                io.emit('join_room_request_result', {
                    nickname: data['nickname_'], 
                    room: data['roomname'], 
                    result: 0, 
                    banned: 1
                });
            } else {
                socket.join(data['roomname']);
                socket.room = data['roomname'];
                if (!room.users.includes(data['nickname_'])) {
                    room.users.push(data['nickname_']); 
                }
                io.emit('join_room_request_result', {
                    nickname: data['nickname_'], 
                    room: data['roomname'], 
                    result: 1
                });
            }
        } else {
            io.to(socket.id).emit('join_room_request_result', {
                nickname: data['nickname_'], 
                room: data['roomname'], 
                result: 0, 
                message: "Room does not exist."
            });
        }
    });

    // get all users in room
    socket.on('get_user_in_room', function(data){
        for (const room of rooms){
            if (room.room_name == data['room']){
                io.emit('get_user_in_room_result', {result: 1, list: room.users, nickname: data['nickname_'], owner:room.room_admin, coadmin:room.room_coadmin});
            }
            return;
        }
        io.emit('get_user_in_room_result', {result: 0, nickname: data['nickname_']});
    });
});