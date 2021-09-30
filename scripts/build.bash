./kphp2cpp --composer-root $(pwd) --mode cli --extra-linker-flags='-ldl -L${KPHP_PATH}/objs/flex -ggdb' ./main.php
