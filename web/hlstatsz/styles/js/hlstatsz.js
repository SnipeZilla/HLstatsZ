/*  2025.02.2x - hlstatsz.js
 *  https://SnipeZilla.com
 */
const hlz={
    ajax: async function(url, tr = false) {
        try {
            //this.Time = Date.now();
    
            const response = await fetch(url + (/\?/.test(url) ? '&' : '?') + 'token=' + hlz.token);
            
            if (!response.ok) throw new Error(await response.text());
    
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let done = false;
            let jsonData = '';
    
            while (!done) {
                const { value, done: readerDone } = await reader.read();
                done = readerDone;
                if (value) {
                    jsonData += decoder.decode(value, { stream: !done });
                }
            }
            if (!jsonData) return false;
            // Process accumulated JSON data
            const data = JSON.parse(jsonData);
            //console.log(Date.now() - this.Time, data);
    
            // Handling different types of data
            if (data.config || data.stats || data.trend) hlz.global(data);
            if (data.server) hlz.serverStats(data);
            if (data.ping) hlz.ping(data);
            if (data.id) hlz.profile(data);
            if (data.load) hlz.chart(data.load[0], 0);
            if (data.dawards) hlz.awards(data);
            if (data.map) hlz.maps(data);
            if (data.dl) hlz.dl(data);
            if (data.alert) alert(data.alert);
            if (data.error) location.reload();
    
            // Smooth scroll if 'tr' is provided
            if (tr) {
                setTimeout(() => {
                    window.scrollTo({ top: tr.getBoundingClientRect().top + window.scrollY, behavior: 'smooth' });
                }, 250);
            }
        } catch (error) {
            console.log('Error:', error);
            alert('An error occurred: ' + error.message);
        }
    },
    awards: function(data) {
        let card=document.querySelectorAll('.hlz-awards .hlz-card');
        card[0].innerHTML=data.dawards;
        card[1].innerHTML=data.daily;
        card[2].innerHTML=data.ranks;
        card[3].innerHTML=data.rank30;

    },
    chart: function(data,card) {
        switch(card) {
            case 0:
                if ( document.querySelector(".lchart").innerHTML ) return false;

                var i=data['map'].length-1,
                    map=data['map'][i],
                    x=0
                    x2=data['time'][i],
                    fill=['transparent','#B3F7CA'],
                    ii=1,
                    annotation=new Array();
                while ( i > 0 ) {
                    if ( data['map'][i] !== map ) {
                        x=data['time'][i];
                        annotation.push({
                            x: x,
                            x2:x2,
                            borderColor: '#404346',
                            fillColor: fill[ii],
                            opacity:0.1,
                            label: {        
                                style: {
                                fontSize: '14px',
                                color: '#ddd',
                                background: '#161616' },                  
                            text: map }
                        });
                        ii=((ii+1)% 2);
                        map=data['map'][i];
                        x2=data['time'][i];
                    }
                    if ( map == '' ) break;
                    i--;
                
                }
                var load = { 
                        theme: {mode: 'dark',palette: 'palette1'},
                        animations: { enabled: false},
                        //colors:['#FF0000','#0D6EFD'],
                        series: [{ type: 'area',
                                   name: 'Act Players',
                                   data: data['act_player'] },
                                 { name: 'Max Players',
                                   data: data['max_player'] },
                                 { name: 'Uptime',
                                   data: data['uptime'] },
                                 { name: 'fps',
                                   data: data['fps'] } ],
                        chart: { toolbar: {
                                        show: true,
                                        tools: {customIcons: [{
                                     icon: '<img src="styles/css/images/map.png" style="width:20px;margin:0 4px;">',
                                     index: 4,
                                     title: 'Show/Hide Maps',
                                     click: function (chart, options, e) {
                                       document.querySelector('g.apexcharts-xaxis-annotations').style.display=document.querySelector('g.apexcharts-xaxis-annotations').style.display?'':'none';
                                     }
                                     }]}},
                                 zoom: {
                                     enabled: true,
                                     type: 'xy',  
                                     autoScaleYaxis: true,  
                                     allowMouseWheelZoom: true
                                 },
                                 background: 'inherit',
                                 height: 300,
                                 type: 'line'},
                        stroke: { width:[1,4,1,1],
                                  curve: 'smooth' },
                        xaxis: { type: 'datetime',
                                 categories: data['time'],
                                 min: data['time'][data['time'].length-1]-86400000,
                                 max: data['time'][data['time'].length-1]+3600000,
                                 labels: {
                                     show: true,
                                     hideOverlappingLabels: true,
                                     datetimeUTC: false,
                                     datetimeFormatter: {
                                         year: 'yyyy',
                                         month: "MMM yyyy",
                                         day: 'MMM dd',
                                         hour: 'HH:mm',
                                         minute: 'HH:mm:ss',
                                         second: 'HH:mm:ss',
                                     },
                                 },
                        },
                        annotations:{ xaxis: annotation },
                        yaxis: [{ min:0,
                                  max:parseInt(data['max_player'][data['max_player'].length-1]),
                                  decimalsInFloat: 0,
                                  title: {text: "Players"} },
                                { min:0,
                                  max:parseInt(data['max_player'][data['max_player'].length-1]),
                                  decimalsInFloat: 0,
                                  show: false, opposite:true },
                                { show: false, opposite:true,
                                  labels: {
                                      formatter: function(value) {
                                      var h=Math.floor(value / 60);
                                      var m=h % 60;
                                      return h+'h '+m+'mn';
                                  }
                                }},
                                { show: false, opposite:false } ],
                        tooltip: {x: {format: 'MMM dd, yyyy HH:mm'}}
                };
                var chart = new ApexCharts(document.querySelectorAll(".lchart")[0], load);
                chart.render().then(() => document.querySelector('g.apexcharts-xaxis-annotations').style.display='none');
            break;

            case 2:
                var skill = { 
                        theme: {mode: 'dark',palette: 'palette1'},
                        series: [{ type: 'area',
                                   name: 'Skill',
                                   data: data['total_skill'] },
                                 { name: 'Session',
                                   data: data['total_time'] }],
                        chart: { background: 'inherit',
                                 zoom: {
                                     enabled: true,
                                     type: 'xy',  
                                     autoScaleYaxis: true,  
                                     allowMouseWheelZoom: true
                                 },
                                 height: 350,
                                 type: 'line' },
                        dataLabels: { enabled: false },
                        stroke: { width:2,curve: 'smooth' },
                        xaxis: { type: 'datetime',
                                 categories: data['timestamp'],
                                 labels: {
                                     show: true,
                                     hideOverlappingLabels: true,
                                     datetimeUTC: false,
                                     datetimeFormatter: {
                                         year: 'yyyy',
                                         month: "MMM yyyy",
                                         day: 'MMM dd',
                                         hour: 'HH:mm',
                                         minute: 'HH:mm:ss',
                                         second: 'HH:mm:ss',
                                     },
                                 },
                        },
                        yaxis: [{ title: {text: "Skill"} },
                                { labels: {
                                      formatter: function(value) {
                                          var h=('0'+(Math.floor(value / 3600))).slice(-2);
                                          var m=('0'+(Math.floor((value % 3600)/60))).slice(-2);
                                          var s=('0'+(value % 60)).slice(-2);
                                          return h+':'+m+':'+s;
                                      }
                                  },
                                  opposite: true,
                                  type: 'datetime',
                                  title: {text: "Session"} }],
                        tooltip: {x: {format: 'dMMM dd, yyyy HH:mm'}}
                };
                var chart = new ApexCharts(document.querySelectorAll(".chart")[0], skill);
                chart.render();
		    
                var kills = {
                        theme: {mode: 'dark'},
                        colors:['#008ffb','#ff4560','#00e396','#775dd0','#ffb01a'],
                        series: [{ type: 'area',
                                   name: 'Kills',
                                   data: data['total_kills'] },
                                 { type: 'area',
                                   name: 'Death',
                                   data: data['total_deaths'] },
                                 { name: 'Headshots',
                                   data: data['total_hs'] },
                                 { name: 'Kill Streak',
                                   data: data['total_kill_streak'] },
                                 { name: 'Death Streak',
                                   data: data['total_death_streak'] }],
                        chart: { background: 'inherit',
                        zoom: {
                            enabled: true,
                            type: 'xy',  
                            autoScaleYaxis: true,  
                            allowMouseWheelZoom: true
                        },
                                 height: 350,
                                 type: 'line' },
                        dataLabels: {enabled: false},
                        stroke: {width:2,curve: 'smooth'},
                        xaxis: { type: 'datetime',
                                 categories: data['timestamp'],
                                 labels: {
                                     show: true,
                                     hideOverlappingLabels: true,
                                     datetimeUTC: false,
                                     datetimeFormatter: {
                                         year: 'yyyy',
                                         month: "MMM yyyy",
                                         day: 'MMM dd',
                                         hour: 'HH:mm',
                                         minute: 'HH:mm:ss',
                                         second: 'HH:mm:ss',
                                     },
                                 },
                        }
                    };
                var chart = new ApexCharts(document.querySelectorAll(".chart")[1], kills);
                chart.render();
            break;

            case 7:
                var stats = {
                        theme: {mode: 'dark',palette: 'palette1'},
                        series: [{ name: "Killer",
                                   data: data['killer']['pos'] },
                                 { name: "Death",
                                   data: data['victim']['pos_victim'] },
                                 { name: "Victim",
                                   data: data['killer']['pos_victim'] },
                                 { name: "Killed By",
                                   data: data['victim']['pos'] }],
                        chart: { toolbar: {show: false},
                                 background: 'inherit',
                                 height: 450,
                                 width:450,
                                 zoom: {enabled: true},
                                 type: 'scatter'},
                        markers: { shape: ['star', 'star', 'triangle', 'triangle'],
                                  size: 8,
                                  fillOpacity: 1,
                                  strokeColors: '#404346',
                                  strokeWidth: [1, 1, 1, 1] },
                        colors: ['yellow','orangered','HotPink','Lime'],
                        xaxis: {min: parseInt(data['mm'][0]),max:parseInt(data['mm'][1]),tickAmount:10},
                        yaxis: {}
                    };
                var chart = new ApexCharts(document.querySelectorAll(".chart")[3], stats);
                chart.render();
            break;
            default:
            break;

        }
    },
    chats: function(e){

        var options= {
            rowCallback:function(row, data) {
                var time=row.querySelector('.hlz-rank').innerHTML;
                row.querySelector('.hlz-rank').innerHTML=new Date(time*1000).toLocaleString();
            },
            preDrawCallback: function (d) {
                if (  d.json ) {
                    if (hlz.$.draw === d.json.draw) return false;
                    hlz.$.draw=d.json.draw;
                } 
            },
            drawCallback: function (d) {
                if ( typeof d.json == "undefined" || d.json.data == null ) return false;
                hlz.$.draw=d.json.draw+1;
                document.querySelectorAll('#players tbody td.dt-control').forEach(el => el.onclick= function() {

                    tr = el.closest('tr');
                    row = hlz.table.row(tr);
                    if (tr.classList.contains('dt-hasChild')) {

                        tr.classList.remove('details','selected');
                        row.child.hide();

                    } else {

                        let trElement = document.querySelector('#players tr[data-dt-row]');
                        if (trElement) {
                            trElement.remove();
                            document.querySelector('.dt-hasChild').className = '';
                        }
                        tr.classList.add('details','selected');
                        id   = {'playerId': tr.getElementsByTagName("td")[1].querySelector('span').getAttribute('data-player'),'last_event':hlz.$.now-hlz.$.config['profile']};
                        row.child( hlz.pCard ).show();
                        hlz.ajax(hlz.url.profile+'?game='+hlz.$.game+'&player='+JSON.stringify(id)+'&fname='+encodeURIComponent(JSON.stringify(hlz.$.fname)),tr);
                        window.scrollTo({ top: tr.getBoundingClientRect().top + window.scrollY, behavior: 'smooth' });

                    }
                });
            },
            layout: {
                topStart: {
                    div: {
                        id: 'hlz-topstart'
                    }
                }
            },
            pageLength: 30,
            ajax: {
                url: this.url.chats
            },
            columns: [
                { data: 'time', class: 'dt-control',orderSequence: ['desc','asc'] },
                { data: 'lastName',orderSequence: ['desc','asc'] },
                { data: 'message', orderSequence: ['desc','asc'] },
                { data: 'server', orderSequence: ['desc','asc']  },
                { data: 'map', orderSequence: ['desc','asc']  }
            ],
            deferLoading:0,
            searchDelay: 1500,
            responsive: {details:false},
            order: [[0, 'asc']],
            processing: true,
            serverSide: true
        }
        return options;
    },
    clans: function(e){

        var options= {
            rowCallback:function(row, data) {
                row.setAttribute("data-clan",data.id);
            },
            preDrawCallback: function (d) {
                if (  d.json ) {
                    if (hlz.$.draw === d.json.draw) return false;
                    hlz.$.draw=d.json.draw;
                } 
            },
            drawCallback: function (d) {
                if ( typeof d.json == "undefined" || d.json.data == null ) return false;
                hlz.$.draw=d.json.draw+1;
                document.querySelectorAll('#clans tbody td.dt-control').forEach(el => el.onclick= function(){
		        
                    tr = el.closest('tr');
                    row = table.row(tr);
                    clan=tr.getAttribute('data-clan');
                    members=tr.getElementsByTagName("td")[3].innerHTML;
                    if (tr.classList.contains('dt-hasChild')) {
		        
                        hlz.sid=0;
                        tr.classList.remove('details','selected');
                        row.child.hide();
		        
                    } else {
		        
                        let trElement = document.querySelector('tr[data-dt-row]');
                        if (trElement) {
                            trElement.remove();
                            document.querySelector('.dt-hasChild').className = '';
                        }
                        tr.classList.add('details','selected');
                        row.child( '<table id="players" class="display compact" style="width:100%;">'+
                                    '<thead>'+
                                       '<tr>'+
                                           '<th>Rank</th>'+
                                           '<th>Player</th>'+
                                           '<th>Points</th>'+
                                           '<th>Activity</th>'+
                                           '<th>Connection</th>'+
                                           '<th>Kills</th>'+
                                           '<th>Deaths</th>'+
                                           '<th>Headshots</th>'+
                                           '<th>K:D</th>'+
                                           '<th>HS:K</th>'+
                                       '</tr>'+
                                   '</thead>'+
                                '</table>' ).show();
                        hlz.table = new DataTable('#players', hlz.players(1,hlz.url.clanplayers,{game:hlz.$.game, clan: clan, total: members,token:hlz.token} ));
                    }
		        
                });
            },
            layout: {
                topStart: {
                    div: {
                        id: 'hlz-topstart'
                    }
                }
            },
            pageLength: 30,
            ajax: {
                url: this.url.clans
            },
            columns: [
                { data: 'rank_position', class: 'dt-control dt-icon',orderSequence: ['desc','asc'] },
                { data: 'tag',orderSequence: ['desc','asc'] },
                { data: 'name', orderSequence: ['desc','asc'] },
                { data: 'members', orderSequence: ['desc','asc']  },
                { data: 'clan_skill', orderSequence: ['desc','asc']  },
                { data: 'clan_kills', orderSequence: ['desc','asc'] },
                { data: 'clan_headshots', orderSequence: ['desc','asc'] },
                { data: 'clan_deaths', orderSequence: ['desc','asc'] },
                { data: 'kd', orderSequence: ['desc','asc'] },
                { data: 'hsk', orderSequence: ['desc','asc'] }
            ],
            searchDelay: 1500,
            responsive: {details:false},
            order: [[0, 'desc']],
            processing: true,
            serverSide: true
        }
        if ( e == undefined ) options.deferLoading=0;
        return options;

    },
    dl:function(d) {

        if ( confirm('Download "'+d.file+'"?') ) {
            window.location.href=d.dl;
        }
        //const link = document.createElement('a');
        //// Set the href attribute to the file URL
        //link.href = file;
        //// Set the download attribute to specify the file name
        //link.download = 'map.bsp';
        //// Append the anchor to the body (optional)
        //document.body.appendChild(link);
        //// Programmatically click the link to trigger the download
        //link.click();
        //// Remove the anchor from the body (optional)
        //document.body.removeChild(link);


    },
    game: function(e) {

        if ( e !== undefined ) {

            if ( e == this.$.game && this.$.page != 'live' ) return false;
            if ( this.$.page == 'live' && this.$.game == e ) {
                document.getElementById('hlz-games').querySelector('img[alt="'+e+'"]').parentElement.classList.remove('hlz-active');
                this.$.game='';
                return this.gameStats();
            }

            document.getElementById('hlz-games').querySelectorAll('img').forEach(element => {
                element.parentElement.classList.remove('hlz-active');
            });
            document.getElementById('hlz-games').querySelector('img[alt="'+e+'"]').parentElement.classList.add("hlz-active");
            this.$.game=e;
            setCookie('game', e, 365);
        }

        this.$.fname=this.$.games['server'][this.$.game][0]['fname'];
        this.$.total=this.$.games['stats'][this.$.game]['players'];

        tr=document.getElementById('hlz-profile2');
        if (tr ) {
            tr.style.display='none';
            document.getElementById('profile-award').innerHTML='';
            window.scrollTo(0,0);
        }
        if ( this.$.page == 'players' ) {
            hlz.table.clear().order([0, 'desc']).search("").ajax.url(this.url.players+'?game='+this.$.game+'&total='+this.$.total+'&token='+this.token).draw();
        }
        if ( this.$.page == 'clans' ) {
            table.clear().order([0, 'desc']).search("").ajax.url(this.url.clans+'?game='+this.$.game+'&total='+this.$.total+'&token='+this.token).draw();
        }
        if ( this.$.page == 'chats' ) {
            hlz.table.clear().order([0, 'desc']).search("").ajax.url(this.url.chats+'?game='+this.$.game+'&servers='+encodeURIComponent(JSON.stringify(this.$.games['server'][this.$.game]))+'&token='+this.token).draw();
        }
        if ( this.$.page == 'bans' ) {
            hlz.table.clear().order([0, 'desc']).search("").ajax.url(this.url.players+'?game='+this.$.game+'&total='+this.$.total+'&bans&token='+this.token).draw();
        }
        if ( this.$.page == 'awards' ) {
            hlz.ajax(hlz.url.awards+'?game='+this.$.game);
        }
        if ( this.$.page == 'maps' ) {
            table.clear().search("").ajax.url(hlz.url.maps+'?game='+this.$.game+'&token='+this.token).draw();
        }
        this.gameStats();



    },
    gameStats: function() {

        if ( this.$.page == 'live' ) {

            for (var game in hlz.$.games['server']){
                document.querySelectorAll('.leaflet-marker-icon').forEach(element => {
                    if ( !element.classList.contains('server') ) {
                        if ( element.classList.contains(this.$.game) || !this.$.game ) { element.style.opacity = '1' }
                        else { element.style.opacity = '0.25' }
                    }
                });
            }


            if ( !this.$.game ) {
                document.getElementById('hlz-topstart').innerHTML='';
                return;
            }

        }
        
        var server     = this.$.games['server'][this.$.game].length,
            name       = this.$.games['server'][this.$.game][0]['name'],
            players    = this.$.games['stats'][this.$.game]['players'],
            kills      = this.$.games['stats'][this.$.game]['kills'],
            headshots  = this.$.games['stats'][this.$.game]['headshots'],
            newplayers = '',
            newkills   = '';

            if ( this.$['trend'] && this.$['trend'][this.$.game] ) {

               if (parseInt(this.$['trend'][this.$.game] ['players'])) newplayers=' (+'+this.$['trend'][this.$.game]['players']+')';
               if (parseInt(this.$['trend'][this.$.game]['kills'])) newkills=' (+'+this.$['trend'][this.$.game]['kills']+')';

            }

        document.getElementById('hlz-topstart').innerHTML = '<span class="hlz-game">'+name+'</span>'+
                                                            ' Tracking '+
                                                            '<span class="hlz-players">'+this.num(players)+newplayers+'</span>'+
                                                            ' players with '+
                                                            '<span class="hlz-kills">'+this.num(kills)+newkills+'</span> kills'+
                                                            ' and '+
                                                            '<span class="hlz-hs">'+this.num(headshots)+'</span> headshots '+
                                                            '(<span class="hlz-percent">'+(kills?(Math.round((headshots/kills) * 10000) / 100):0)+'%</span>)'+
                                                            ' on '+
                                                            '<span class="hlz-server">'+server+'</span> '+(server>1?'servers':'server');

    },
    global: function(data) {

        if ( !this.$.game && ( this.$.page != 'live' ) ) {
            var Game=getCookie('game');
            if ( Game ) {
                 this.game(Game);
            } else {                
                for ( var key in this.$.games['server'] ) {
                
                    this.game(key);
                    break;
                
                }
            }

        }

        for (var key in data){

            if ( key == 'config' ) {

                this.$.config=data[key];
                continue;

            } else if ( key == 'trend' ) {

                this.$['trend']={};
                for (var i = 0; i < data[key].length; i++) {
                    this.$['trend'][data[key][i]['game']]=data[key][i];
                } 
                continue;

            } else if ( key == 'stats' ) {

                for ( var k in data[key][0] ) {

                    this.$[k]=data[key][0]?data[key][0][k]:data[key][k];

                }

                document.getElementById('hlz-global').innerHTML='Tracking '+
                                                                '<span class="hlz-players">'+this.num(this.$['players'])+'</span> players'+
                                                                ' with '+
                                                                '<span class="hlz-kills">'+this.num(this.$['kills'])+'</span> kills'+
                                                                ' and '+
                                                                '<span class="hlz-hs">'+this.num(this.$['headshots'])+'</span> headshots '+
                                                                '(<span class="hlz-percent">'+(Math.round((this.$['headshots']/this.$['kills']) * 10000) / 100)+'%</span>)'+
                                                                ' and '+
                                                                '<span class="hlz-deaths">'+this.num(this.$['deaths'])+'</span> deaths '+
                                                                '(<span class="hlz-percent">'+(Math.round((this.$['deaths']/this.$['kills']) * 10000) / 100)+'%)</span>'+
                                                                ' on '+
                                                                '<span class="hlz-server">'+this.$.games['stats']['server']+'</span> '+(this.$.games['stats']['server']>1?'servers':'server')+'</span>';	        
                continue;

            }

        }

    },
    maps: function(){

        var options= {
            drawCallback: function (d) {
                let search = document.querySelector('input[type="search"]');
                if ( search ) {
                    search.addEventListener('input', function(e){
                        if ( search.value.trim() == '' ) {
                            search.value=search.value.trim();
                            return false;
                        }
                    })
                }
                if ( typeof d.json == "undefined" || d.json.data == null ) return false;
                document.querySelectorAll('#maps .download').forEach(el => el.onclick= function() {
                    var map=el.querySelector('img').getAttribute('data-map');
                    hlz.ajax(hlz.url.maps+'?download='+map+'&token='+hlz.token);

                })
            },
            layout: {
                topStart: {
                    div: {
                        id: 'hlz-topstart'
                    }
                }
            },
            pageLength: 30,
            ajax: {
                url: this.url.maps
            },
            columns: [
                { data: 'rank_position',orderSequence: ['desc','asc'] },
                { data: 'map', orderSequence: ['desc','asc'] },
                { data: 'kills', orderSequence: ['desc','asc'] },
                { data: 'headshots', orderSequence: ['desc','asc'] },
                { data: 'hsk', orderSequence: ['desc','asc'] },
                { data: 'trend', orderSequence: ['desc','asc'] },
                { data: 'dl', orderable: false }
            ],
            deferLoading: 0,
            searchDelay: 1500,
            order: [[0, 'desc']],
            responsive: true,
            processing: true,
            serverSide: true
        }
        return options;

    },
    // index.php Live view: individual server table
    server: function(){

        document.querySelectorAll('#servers tbody td.dt-control').forEach(el => el.onclick= function(){

            tr = el.closest('tr');
            data = tr.getAttribute('data-server').split('-');
            hlz.sid = data[1];
            game= data[0];
            row = table.row(tr);
            hlz.$.fname=tr.getElementsByTagName("td")[1].innerHTML;

            if (tr.classList.contains('dt-hasChild')) {

                hlz.sid=0;
                tr.classList.remove('details','selected');
                row.child.hide();

            } else {

                let trElement = document.querySelector('tr[data-dt-row]');
                if (trElement) {
                    trElement.remove();
                    document.querySelector('.dt-hasChild').className = '';
                }
                tr.classList.add('details','selected');
                row.child(  '<div class="hlz-load">'+
                                '<div class="charts"><div class="lchart"></div></div>'+
                            '</div>'+
                            '<table id="players" class="display compact" style="width:100%;" data-server="'+hlz.sid+'">'+
                            '<thead>'+
                               '<tr>'+
                                   '<th data-priority="1"></th>'+
                                   '<th data-priority="1">Name</th>'+
                                   '<th>Played</th>'+
                                   '<th>Kills</th>'+
                                   '<th>Headshots</th>'+
                                   '<th>Hsk</th>'+
                                   '<th>Deaths</th>'+
                                   '<th>Kd</th>'+
                                   '<th>Skill</th>'+
                                   '<th>Change</th>'+
                               '</tr>'+
                           '</thead>'+
                        '</table><div id="hlz-chat">></div>').show();
                hlz.table = new DataTable('#players', {
                    language: {
                        emptyTable: 'No Players'
                    },
                    preDrawCallback: function () {
                        hlz.TimerOff();
                    },
                    rowCallback:function(row, data) {

                        row.classList.add(data.team,game);
                        if ( /bot\.svg/.test(data.flag) ) {
                            row.querySelector('td.dt-control').classList.remove('dt-control');
                        }

                    },
                    drawCallback: function(d){
                        if ( typeof d.json == "undefined" || d.json.data == null ) return false;
                        if ( d.json['sid'] != hlz.sid ) return false;             
                        document.querySelectorAll('#players tbody td.dt-control').forEach(el => el.onclick= function(){
			                if (el.getElementsByTagName('img')[0].alt.toUpperCase()=='BOT') return false;
                            tr   = el.closest('tr');
                            id   = {'playerId': tr.getElementsByTagName("td")[1].querySelector('span').getAttribute('data-player'),'last_event':hlz.$.now-hlz.$.config['profile']};
                            game = tr.classList[1];
                            row  = hlz.table.row(tr);
			            
                            if (tr.classList.contains('dt-hasChild')) {
			            
                                tr.classList.remove('details','selected');
                                row.child.hide();
			            
                            } else {
			            
                                let trElement = document.querySelector('#server-'+hlz.sid+' tr[data-dt-row]');
                                if (trElement) {
                                    trElement.remove();
                                    document.querySelector('#server-'+hlz.sid+' .dt-hasChild').className = '';
                                }
                                tr.classList.add('details','selected');
                                row.child( hlz.pCard ).show();
			            
                                hlz.ajax(hlz.url.profile+'?game='+game+'&player='+JSON.stringify(id)+'&fname='+encodeURIComponent(JSON.stringify(hlz.$.fname)),tr);
                                window.scrollTo({ top: tr.getBoundingClientRect().top + window.scrollY, behavior: 'smooth' });
			            
                            }
                        });
                        
                    },
                    initComplete: function (d) {
                        if ( hlz.sid > 0 ) hlz.ajax(hlz.url.load+'?sid='+hlz.sid);
                        hlz.serverStats(d.json);
                        hlz.TimerOn();
                    },
                    responsive: {details:false},
                    searching: false,
                    info: false,
                    paging: false,
                    ordering: false,
                    ajax: {
                        url: hlz.url.live,
                        data: { serverId: hlz.sid, teams: JSON.stringify(hlz.$.settings['teams']), token:hlz.token}
                    },
                    columns: [
                        { data: 'flag',class: 'dt-control dt-icon'},
                        { data: 'name'},
                        { data: 'played'},
                        { data: 'kills'},
                        { data: 'headshots'},
                        { data: 'hsk'},
                        { data: 'deaths'},
                        { data: 'kd'},
                        { data: 'skill'},
                        { data: 'change'}
                    ],
                    processing: true,
                    serverSide: false
                });
 
            }

        });

    },
    // index.php Live view: all servers table
    servers: function(e){
        return {
            initComplete: function () {
                document.querySelector('#servers').style.display='';
                hlz.server();
                hlz.ajax(hlz.url.live+'?serverId='+hlz.sid+'&teams='+JSON.stringify(hlz.$.settings['teams']));
                hlz.$.IPs=new Array();
                hlz.$.SIDs=new Array();
                for ( var g in hlz.$.games['server'] ) {
                    for (var i=0; i<hlz.$.games['server'][g].length;i++){
                        hlz.$.IPs.push(hlz.$.games['server'][g][i]['publicaddress']);
                        hlz.$.SIDs.push(hlz.$.games['server'][g][i]['serverId']);
                    }
                } 
                hlz.ajax(hlz.url.ping+'?IPs='+JSON.stringify(hlz.$.IPs)+'&SIDs='+JSON.stringify(hlz.$.SIDs));
            },
            layout: {
                topStart: null
            },
            columns:[null,null,null,null,null,null,null,null,null,null],
            responsive: {details:false},
            searching: false,
            info: false,
            paging: false,
            ordering: false,
            serverside:false
        }
    },
    serverStats: function(e) {

        if ( typeof e.server != undefined && e.server != null ) {
            //update servers
            document.querySelectorAll('tr[data-server]').forEach ( el => {
                var sid = el.getAttribute('data-server').split('-');
                el.getElementsByTagName("td")[3].innerHTML = e.server[sid[1]]['map'][0];
                el.getElementsByTagName("td")[4].innerHTML = e.server[sid[1]]['map'][1];
                el.querySelector('.players').innerHTML     = e.server[sid[1]]['players'];
                el.getElementsByTagName("td")[6].innerHTML = e.server[sid[1]]['bots'];
		    
            });
            //Update Table
            if ( hlz.sid > 0 && hlz.sid == e.sid && e.data.length) {
                hlz.table.clear();
                hlz.table.rows.add(e.data);
                hlz.table.draw();
            } 
            //update chat
            var el=document.getElementById('hlz-chat'),
                  chat='';
            if ( el && hlz.sid > 0 ) {
                for ( var i=0; i<e.server[hlz.sid]['chat'].length; i++ ) {
                    chat+='<div><span class="chatter">>'+e.server[hlz.sid]['chat'][i][0]+'</span> '+e.server[hlz.sid]['chat'][i][1]+'</div>';
                }
                if ( chat ) {
                    el.style.display='block';
                    el.innerHTML=chat;
                }
            } 
            //OpenMap
            document.querySelector('.leaflet-shadow-pane').innerHTML='';
            document.querySelector('.leaflet-marker-pane').innerHTML='';
            document.querySelector('.leaflet-popup-pane').innerHTML ='';
            const LeafIcon = L.Icon.extend({ options: {
                                                shadowUrl: "./styles/css/images/marker-shadow.png",
                                                iconSize:      [25,41],
                                                iconAnchor:    [12,41],
                                                popupAnchor:   [1,-34],
                                                tooltipAnchor: [16,-28],
                                                shadowSize:    [41,41] }
                                          });

            var servers=new Array();
            for ( let games in hlz.$.games['server'] ) {
                var svr=hlz.$.games['server'][games];
                for ( var i=0; i<svr.length; i++ ) {

                    if ( svr[i]['lat'] ) {
                                                
                        if ( !Array.isArray(servers[svr[i]['city']]) ) servers[svr[i]['city']]=new Array();
                        servers[svr[i]['city']].push([svr[i]['lat'],//0
                                                     svr[i]['lng'],//1
                                                     svr[i]['city'],//2
                                                     svr[i]['country'],//3
                                                     svr[i]['name'],//4
                                                     svr[i]['fname'],//5
                                                     svr[i]['act_map'],//6
                                                     '<a href="steam://connect/'+svr[i]['address']+':'+svr[i]['port']+'">'+
                                                     svr[i]['publicaddress']+'</a>']);//7
                                                        
                    }

                    for ( let team in e.server[svr[i]['serverId']]['team'] ) {

                        var player=e.server[svr[i]['serverId']]['team'][team];
                        for ( var ii=0; ii<player.length; ii++) {

                            if ( player[ii]['cli_lat'] ) {
                                var idx=hlz.$.settings['teams'][player[ii]['team']][0];
                                if ( idx >= 0 && idx <= 3 ) {
                                    var t_icon = new LeafIcon({iconUrl: './styles/css/images/team-'+idx+'-marker.png'});
                                } else {
                                    var t_icon = new LeafIcon({iconUrl: './styles/css/images/team-x-marker.png'});                     
                                }
                                var card='<div><span class="openmap-name">'+player[ii]['name']+'</span>'+(player[ii]['cli_state']?player[ii]['cli_state']+', ':'')+player[ii]['cli_country']+'</div>'+
                                '<div><span class="openmap-server">'+svr[i]['fname'].replace(/\\/g, "")+'</span></div>'+
                                '<div>Playing: <span class="openmap-game">'+svr[i]['name']+'</span></div>'+
                                '<div>Since: <span class="openmap-time">'+new Date(player[ii]['connected']*1000).toTimeString()+'</span></div>';
                                var marker=new L.marker([player[ii]['cli_lat'], player[ii]['cli_lng']],{icon: t_icon}).bindPopup(card).addTo(hlz.map);
                                marker._icon.classList.add(games);
                            }

                        }

                    }

                }
            }
            var s_icon = new LeafIcon({iconUrl: './styles/css/images/server-marker.png'});
            for ( let s in servers ) {
                var card='<div><span class="openmap-city">'+servers[s][0][2]+'</span>, <span class="openmap-country">'+servers[s][0][3]+'</span></div>';
                for ( var i=0; i<servers[s].length; i++ ) {
                        card+='<div><span class="openmap-name">'+servers[s][i][4]+'</span></div>'+
                        '<div><span class="openmap-server">'+servers[s][i][5].replace(/\\/g, "")+'</span></div>'+
                        '<div>Map: <span class="openmap-map">'+servers[s][i][6]+'</span></div>'+
                        '<div>Click to join: '+servers[s][i][7]+'</div>';
                }
                var marker=new L.marker([servers[s][0][0], servers[s][0][1]],{icon: s_icon}).bindPopup(card).addTo(hlz.map);
                marker._icon.classList.add('server');
            }
            
        } this.TimerOn();

    },
    ping: function(e){

        document.querySelectorAll('tr[data-server]').forEach(el => {
            var sid   = el.getAttribute('data-server').split('-')[1],
                title = '';
            if ( e['ping'][sid] ) {
                el.getElementsByTagName("td")[9].querySelector('div').className = 'status '+e['ping'][sid];
                if ( e['ping'][sid] == 'online' ) { title='Online'; }
                if ( e['ping'][sid] == 'warning' ) { title='Online but did not respond...'; }
                if ( e['ping'][sid] == 'offline' ) { title='Offline'; }
                el.getElementsByTagName("td")[9].querySelector('div').setAttribute('title',title);
            }
        });

    },
    players: function(e,url,data){

        var layout=e==undefined?{ topStart: { div: { id: 'hlz-topstart' } } }:{topStart:{}};
        var options= {
            preDrawCallback: function (d) {
                if (  d.json ) {
                    if (hlz.$.draw === d.json.draw) return false;
                    hlz.$.draw=d.json.draw;
                    hlz.$.player=d.json.player;
                } 
            },
            drawCallback: function (d) {
                let search = document.querySelector('input[type="search"]');
                if ( search ) {
                    search.addEventListener('input', function(e){
                        if ( search.value.trim() == '' ) {
                            search.value=search.value.trim();
                            return false;
                        }
                    })
                }
                if ( typeof d.json == "undefined" || d.json.data == null ) return false;
                hlz.$.draw=d.json.draw+1;
                if ( Math.floor(d.oAjaxData.start/30) !== hlz.table.page() ) {
                    hlz.table.page(Math.floor(d.oAjaxData.start/30)).draw('page');
                }
                if ( d.json.search ) {
                    document.querySelectorAll('#players thead th').forEach( el => {
                          el.setAttribute("data-dt-order","disable");
                    })
                } else {
                    document.querySelectorAll('#players thead th').forEach( el => {
                          el.removeAttribute("data-dt-order");
                    })
                }
                document.querySelectorAll('#players tbody td.dt-control').forEach(el => el.onclick= function() {

                    tr = el.closest('tr');
                    row = hlz.table.row(tr);
                    if (tr.classList.contains('dt-hasChild')) {

                        tr.classList.remove('details','selected');
                        row.child.hide();

                    } else {

                        let trElement = document.querySelector('#players tr[data-dt-row]');
                        if (trElement) {
                            trElement.remove();
                            document.querySelector('.dt-hasChild').className = '';
                        }
                        tr.classList.add('details','selected');
                        row.child( hlz.pCard ).show();
                        hlz.ajax(hlz.url.profile+'?game='+hlz.$.game+'&player='+encodeURIComponent(JSON.stringify(hlz.$.player[tr.rowIndex-1]))+'&fname='+encodeURIComponent(JSON.stringify(hlz.$.fname)),tr);
                        window.scrollTo({ top: tr.getBoundingClientRect().top + window.scrollY, behavior: 'smooth' });

                    }
                });
            },
            layout:layout,
            pageLength: 30,
            ajax: {
                url: url,
                data: data
            },
            columns: [
                { data: 'rank', class: 'dt-control dt-icon',orderSequence: ['desc','asc'] },
                { data: 'lastName',orderSequence: ['desc','asc'] },
                { data: 'skill', orderSequence: ['desc','asc'] },
                { data: 'activity', orderSequence: ['desc','asc']  },
                { data: 'connection_time', orderSequence: ['desc','asc'] },
                { data: 'kills', orderSequence: ['desc','asc'] },
                { data: 'deaths', orderSequence: ['desc','asc'] },
                { data: 'headshots', orderSequence: ['desc','asc'] },
                { data: 'kd', orderSequence: ['desc','asc'] },
                { data: 'hsk', orderSequence: ['desc','asc'] }
            ],
            searching: e == undefined,
            searchDelay: 1500,
            responsive: {details:false},
            order: [[0, 'desc']],
            processing: true,
            serverSide: true
        }
        if ( e == undefined ) options.deferLoading=0;
        return options;

    },
    num: function(n) {
        if (n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }
        else { return 0 }
    },
    profile2:function(id) {
        tr=document.getElementById('hlz-profile2');
        tr.setAttribute("data-player",id);
        document.getElementById('profile-award').innerHTML=hlz.pCard;
        tr.style.display='block';
        document.querySelector('#hlz-profile2 .close').onclick= function() {
            tr.style.display='none';
            document.getElementById('profile-award').innerHTML='';
            window.scrollTo(0,0);
        }
        hlz.ajax(hlz.url.profile+'?game='+hlz.$.game+'&player='+JSON.stringify({playerId:id,'last_event':hlz.$.now-hlz.$.config['profile']})+'&fname='+encodeURIComponent(JSON.stringify(hlz.$.fname)),tr);
         window.scrollTo({ top: tr.getBoundingClientRect().top + window.scrollY, behavior: 'smooth' });

    },
    pCard: '<div class="hlz-profile">'+
                '<div class="hlz-item-1"><div class="hlz-card"></div></div>'+
                '<div class="hlz-item-2"><div class="hlz-card"></div></div>'+
                '<div class="hlz-item-3"><div class="hlz-card"></div></div>'+
                '<div class="hlz-item-4"><div class="hlz-card"></div></div>'+
                '<div class="hlz-item-5"><div class="hlz-card"></div></div>'+
                '<div class="hlz-item-6"><div class="hlz-card"></div></div>'+
                '<div class="hlz-item-7"><div class="hlz-card"></div></div>'+
                '<div class="hlz-item-8"><div class="hlz-card"></div></div>'+
                '<div class="hlz-item-9"><div class="hlz-card"></div></div>'+
            '</div>',
    profile: function(data) {
        let el= document.querySelector('#players tr.dt-hasChild td:nth-child(2) span') || document.getElementById('hlz-profile2');
        let id= el.getAttribute('data-player');
        let card=document.querySelectorAll('.hlz-card');
        if ( data ) {
            if ( data.id != id ) return false;
            for ( let item in data['card'] ) {
                card[data['card'][item]['card']].innerHTML=data['card'][item]['data'];
                if ( data['card'][item]['card'] == 2 &&
                     data['card'][item]['trend']['timestamp'].length ) this.chart(data['card'][item]['trend'],2);
                if ( data['card'][item]['card'] == 7 &&
                     data['card'][item]['stats']['killer']['pos'] &&
                     data['card'][item]['stats']['killer']['pos'].length  ) this.chart(data['card'][item]['stats'],7);
            }
        }
    },
    Timer:null,
    TimerOff: function() {
        clearTimeout(hlz.Timer);
        hlz.Timer = null;
    },
    TimerOn: function() {

        if ( hlz.Timer === null && hlz.$.config['live'] > 0 ) {
            hlz.Timer=setTimeout( () => {
                hlz.ajax(hlz.url.live+'?serverId='+hlz.sid+'&teams='+JSON.stringify(hlz.$.settings['teams']))
                clearTimeout(hlz.Timer);
                hlz.Timer=null;
            }, hlz.$.config['live']*1000);
        }

    },
    toggle:function(){
        document.querySelector('#hlz-games ul').classList.toggle("visible");
    },
    $:{draw:null,status:0},
    url:{
        awards:      'php/scripts/script.awards.php',
        chats:       'php/scripts/script.chats.php',
        clanplayers: 'php/scripts/script.clanplayers.php',
        clans:       'php/scripts/script.clans.php',
        counter:     'php/scripts/script.counter.php',
        live:        'php/scripts/script.live.php',
        global:      'php/scripts/script.global.php',
        load:        'php/scripts/script.load.php',
        maps:        'php/scripts/script.maps.php', 
        ping:        'php/scripts/script.ping.php',
        players:     'php/scripts/script.players.php',
        profile:     'php/scripts/script.profile.php',
    },
    token:document.querySelector('meta[name="token"]').getAttribute('content')
}

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  let expires = "expires="+d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
  let name = cname + "=";
  let ca = document.cookie.split(';');
  for(let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

document.addEventListener('mousedown', function(event) {
    if ( event.target.tagName === 'HTML' || event.target.tagName === 'UL' ) { event.preventDefault();return false; }
    if( !event.detail || event.detail == 1 ) { return true; }
    else { event.preventDefault();return false; }   
})

