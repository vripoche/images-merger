var ImageMerger = {
    panelId: 'image-panel',
    serviceUrl: './call.php',
    pause: 2000,
    image: null,
    isActive: false,
    imageBlend: {
        mode: ['normal', 'multiply', 'lighten', 'darken', 'lightercolor', 'darkercolor', 'difference', 'screen', 'exclusion', 'overlay', 'softlight', 'hardlight', 'colordodge', 'colorburn', 'lineardodge', 'linearburn', 'linearlight', 'vividlight', 'pinlight', 'hardmix'],
        amount: 'f/0-1'
    },
    imageFunctions:[
        {name: 'mosaic', params: {blockSize:"i/0-100"}}
    ],
    initialize : function() {
        var myself = this;
        setInterval(function() {
            if(myself.isActive) {
                myself.go();
            }
        }, this.pause);
    },
    go: function() {
        var myself = this;
        //this.transform($('#image-container'));
        $.get(myself.serviceUrl, function(json) {
            if(json && json.status == "success" && json.data.image) {
                if($('#image')) {
                    $('#image').remove();
                }
                $('#image-container').append('<img id="image" alt="" src="/' + json.data.image  +  '" />');
                myself.merge();
                $.ajax({url:myself.serviceUrl, type:'DELETE', data:{'image':json.data.image, 'thumb':json.data.thumb}});
            }
        });
    },
    merge: function() {
        var myself = this;
        Pixastic.process(document.getElementById(this.panelId), "blend", {
            amount : myself.parseParam(myself.imageBlend.amount),
            mode : myself.parseParam(myself.imageBlend.mode),
            image : document.getElementById('image')
        });       
    },       
    transform: function(image) {
        image.pixastic("mosaic", {blockSize:10});
    },
    start: function() {
        this.isActive = true;
    },
    stop: function() {
        this.isActive = false;
    },
    parseParam: function(param) {
        if(typeof(param) == 'string') {
            var type = param.substring(0, 1);
            var boundaries = (param.substring(3)).split('-');
            var diff = boundaries[1] - boundaries[0];
            var result = boundaries[0] + Math.random() * diff;
            switch(type) {
                case 'i':
                    result = Math.ceil(result);
                break;
            }
            return result;
        } else if($(param).size()) {
            var index = Math.floor(Math.random() * $(param).size());
            return param[index];
        }
    }
}

var Panel = {
    serviceUrl: './call.php',
    formItem: $('#config-panel form'),
    urlItem: $('#url'),
    submitItem: $('#submit'),
    pause: 2000,
    isAppeared: false,
    isDown: false,
    downPanel: null,
    timer: null,
    initialize: function() {
        var myself = this;
        this.formItem.submit(function(event) {
            event.preventDefault()
            myself.submit();
        });
        if(this.formItem.attr('action') == 'stop') {
            ImageMerger.start();
        }
        this.appear();
        this.toggle();
    },
    appear: function(){
        var myself = this;
        $('#panel').mouseover(function() {
            $('#navigation li').fadeIn();
            myself.isAppeared = true;
            clearTimeout(myself.timer);
        });
        $('#panel').mouseleave(function() {
            myself.timer = setTimeout(function() {
                if(myself.isAppeared && ! myself.downPanel) {
                    $('#navigation li').fadeOut();
                    myself.isAppeared = false;
                }
            }, myself.pause);
        });
    },
    toggle: function(){
        var myself = this;
        $("#navigation li a").click(function (event) {
            var panelToOpen = $('#' + $(this).attr('href'));
            event.preventDefault();
            $("#navigation li a").removeClass('hover');
            if(! myself.downPanel) {
                panelToOpen.slideDown("slow");
                $(this).addClass('hover');
                myself.downPanel = panelToOpen;
            } else if(myself.downPanel.attr('id') != panelToOpen.attr('id')) {
                 $(this).addClass('hover');
                 myself.downPanel.slideUp("slow", function() {
                    panelToOpen.slideDown("slow");
                });
                myself.downPanel = panelToOpen;
            } else {
                panelToOpen.slideUp("slow");
                myself.downPanel = null;
            }
        });
    },
    submit: function() {
        var myself = this;
        $.post(this.serviceUrl, {action: this.formItem.attr('action'), url: this.urlItem.val()}, function(json) {});
        var action = 'start';
        if(myself.formItem.attr('action') == 'start') {
            action = 'stop';
            this.urlItem.attr("disabled", true);
            ImageMerger.start();
        } else {
            this.urlItem.attr("disabled", false);
            ImageMerger.stop();
        }
        myself.formItem.attr('action', action);
        myself.submitItem.val(action);
    }
}

$(window).load(function() {
   ImageMerger.initialize();
   Panel.initialize();
});



