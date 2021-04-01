import $ from 'jquery'
import clippy from 'clippyjs'


function buildDialog (options, agent) {
    let container = document.createElement('div');
    container.classList.add('cover-clippy-dialog', 'content');


    let closeButton = document.createElement('button');
    closeButton.classList.add('delete', 'is-pulled-right');
    closeButton.addEventListener('click', () => {
        this._complete();
        this._finishHideBalloon();
    });

    container.append(closeButton);

    if (options.title) {
        let title = document.createElement('h2');
        title.classList.add('title', 'is-6');
        title.append(options.title);
        container.append(title);
    }


    if (options.text) {
        let text = document.createElement('p');
        text.append(options.text);
        container.append(text);
    }

    if (options.options) {
        let ul = document.createElement('ul');

        for (let option of options.options) {
            let li = document.createElement('li');
            let link = document.createElement('a');

            if (option.link)
                link.href = option.link;
            else if (option.speak)
                link.addEventListener('click', () => this.speak(this._complete, option.speak, option.hold || false));
            else if (option.dialog)
                link.addEventListener('click', () => this.choice(this._complete, option.dialog));
            else if (option.animation === 'random')
                link.addEventListener('click', () => {
                    this._complete();
                    this._finishHideBalloon();
                    agent.animate();
                });
            else if (option.animation)
                link.addEventListener('click', () => {
                    this._complete();
                    this._finishHideBalloon();
                    agent.play(option.animation);
                }); 

            link.append(option.text);

            li.append(link);
            ul.append(li);
        }

        container.append(ul);
    }

    return container;
}

function monkeyPatch (agent) {
    agent.show = function(fast) {
        this._hidden = false;
        if (fast) {
            this._el.show();
            this.resume();
            this._onQueueEmpty();
            return;
        }

        if (this._el.css('top') === 'auto' || !this._el.css('left') === 'auto') {
            this._el.show();
            let vw = document.documentElement.clientWidth;
            let vh = document.documentElement.clientHeight;
            let rect = this._el[0].getBoundingClientRect();
            let left = Math.min(vw * 0.8, vw - rect.width);
            let top = Math.min(vh * 0.8, vh - rect.height);
            this._el.css({ top: top, left: left, display: 'none'});
        }

        this.resume();
        this.play('Show');
        this.reposition();
    }

    agent.choice = function (text) {
        this._addToQueue(function (complete) {
            this._balloon.choice(complete, text);
        }, this);
    };

    agent.onClick = function (func) {
        // Don't block doubleclicks
        let clicks = 0;
        this._el.on('click', (event) => {
            clicks++;
            if (clicks == 1)
                setTimeout(() => {
                    if (clicks == 1)
                        func.call(this, event);
                    clicks = 0;
                }, 300);
        });
    };

    agent._balloon._isOut = function () {
        let vw = document.documentElement.clientWidth;
        let vh = document.documentElement.clientHeight;
        let rect = this._balloon[0].getBoundingClientRect();
        let m = this._BALLOON_MARGIN;

        return rect.top < -m
            || rect.right > vw + m
            || rect.bottom > vh + m
            || rect.left < -m;
    }

    agent._balloon.choice = function (complete, options) {
        this._hold = true;
        this._complete = complete;

        this._hidden = false;
        this.show();

        let c = this._content;
        // set height to auto
        c.height('auto');
        c.width('auto');


        // c.html('');
        c.html(buildDialog.call(this, options, agent));

        this.reposition();
    }
}

let mainDialog = {}
Object.assign(mainDialog, {
    title: "Hi, I'm Clippy! What can I do for you?",
    text: "I'm Cover's new digital assistent and I can make your life easier! What do you need help with?",
    options: [
        {
            text: 'I want to join a club',
            link: '/clubs.php',
        },
        {
            text: 'Find me a job',
            link: '/career.php',
        },
        {
            text: 'Find me something to do',
            link: '/commissies.php',
        },
        {
            text: 'Help me pass my General Linguistics exam!',
            link: '/agenda.php?agenda_id=3503',
        },
        {
            text: 'I\'m bored',
            animation: 'random',
        },
        {
            text: 'I want to have fun',
            link: '/announcements.php?view=read&id=319',
        },
        {
            text: 'Show me what "the before times" were like',
            dialog: {
                title: 'Are you sure?',
                text: 'You want to know what "the before times" were like. Obtaining this knowledge is known to have side effects including but not limited to: sadness, nostalgia, anger, owning a purple hat, melancholy, crying and a higher risk of being sad. Are you sure this is worth it?',
                options: [
                    {
                        text: 'Yes, show me what "the before times" were like.',
                        link: '/fotoboek.php',
                    },
                    {
                        text: "No, it's not worth the risk.",
                        dialog: mainDialog,
                    }
                ]
            },
        },
        {
            text: 'Tell me the date',
            speak: 'Today\'s the First of April ;)',
        }
    ],
});

clippy.load('Clippy', (agent) => {
    monkeyPatch(agent);

    agent.onClick((event) => {
        agent.stopCurrent();
        agent.choice(mainDialog);
    });

    // do anything with the loaded agent    
    agent.show();
    agent.reposition();
    agent.choice(mainDialog);
});
