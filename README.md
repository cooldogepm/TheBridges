# TheBridge

<p align="center">
	<a href="https://github.com/cooldogedev/TheBridge"><img
            src="https://github.com/cooldogedev/TheBridge/blob/main/assets/icon.png?raw=true"/></a><br>
	An extremely customizable TheBridge mini-game designed for scalability and simplicity.
</p>

## Features

- Customisable messages and scoreboard
- Multi arena support
- Waiting lobby support
- Auto-queue support
- Game statistics
- SQLite support
- MySQL support

## Commands

|     Command      |             Description              |           Permission           |
|:----------------:|:------------------------------------:|:------------------------------:|
| thebridge create |         Create a new arena.          | `thebridge.subcommand.create` |
| thebridge delete |      Delete an existing arena.       | `thebridge.subcommand.delete` |
|  thebridge list  |      List all available arenas.      |  `thebridge.subcommand.list`  |
|  thebridge test  | Force online players to join a game. |  `thebridge.subcommand.quit`  |
|  thebridge join  |             Join a game.             |  `thebridge.subcommand.join`  |
|  thebridge quit  |             Quit a game.             |  `thebridge.subcommand.quit`  |

### Arena creation

#### Format

`thebridge create <name: string> <lobby: string> <world: string> <countdown: int> <duration: int> <grace_duration: int> <end_duration: int> <mode: solo|duo|trio|squad>`

#### Example

`thebridge create name game-lobby game-world 30 600 5 10 solo`
