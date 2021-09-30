## KPHP Game

## Building a game

You will need a [KPHP](https://github.com/VKCOM/kphp/) that supports [FFI](https://wiki.php.net/rfc/ffi).
If [Pull295](https://github.com/VKCOM/kphp/pull/295) is not merged yet, you'll need to build a kphp2cpp from that branch.

Our build scripts expect a symlink to an appropriate kphp2cpp in the root of the project.

Example layout:

```
kphp-game/
  kphp2cpp <- this is a symlink you need to provide
  scripts/...
  src/...
```

```bash
# If successfull, game binary can be found at ./bin/game
$ make game
```

Tested on:

* Ubuntu
