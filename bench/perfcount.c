// Minimal perf_event_open wrapper: counts userspace HW events for a child process.
// No `perf` binary and no root needed when /proc/sys/kernel/perf_event_paranoid <= 2
// (we set exclude_kernel). Works on WSL2 when the guest exposes the PMU
// (check: ls /sys/devices/cpu/events should list `instructions`, `cycles`, ...).
//
//   gcc -O2 -o bench/perfcount bench/perfcount.c
//   bench/perfcount <cmd> [args...]
//
// Prints one line to stderr:
//   PERF instructions=<n> cycles=<n> branch-misses=<n> cache-misses=<n>
//
// Instruction counts are frequency-independent, so they're stable on battery,
// under load, and across machines -- far better than wall-clock for resolving
// a sub-percent engine cost.
#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <errno.h>
#include <sys/wait.h>
#include <sys/ioctl.h>
#include <linux/perf_event.h>
#include <asm/unistd.h>

static long perf_open(struct perf_event_attr *a, pid_t pid) {
    return syscall(__NR_perf_event_open, a, pid, -1, -1, 0);
}

static int mkcounter(unsigned type, unsigned long long config, pid_t pid) {
    struct perf_event_attr a;
    memset(&a, 0, sizeof(a));
    a.type = type;
    a.size = sizeof(a);
    a.config = config;
    a.disabled = 1;            // start off...
    a.enable_on_exec = 1;      // ...auto-enable when the child execs
    a.exclude_kernel = 1;      // userspace only (allowed at paranoid=2)
    a.exclude_hv = 1;
    a.inherit = 1;             // follow into the exec'd image
    int fd = (int) perf_open(&a, pid);
    if (fd < 0) fprintf(stderr, "perf_open(type=%u,config=%llu) failed: %s\n", type, config, strerror(errno));
    return fd;
}

static long long readval(int fd) {
    long long v = -1;
    if (fd >= 0 && read(fd, &v, sizeof(v)) != sizeof(v)) v = -1;
    return v;
}

int main(int argc, char **argv) {
    if (argc < 2) { fprintf(stderr, "usage: %s <cmd> [args...]\n", argv[0]); return 2; }

    int pfd[2];
    if (pipe(pfd) != 0) { perror("pipe"); return 3; }

    pid_t pid = fork();
    if (pid < 0) { perror("fork"); return 3; }

    if (pid == 0) {
        // child: wait for parent to arm counters, then exec
        close(pfd[1]);
        char b; if (read(pfd[0], &b, 1) < 0) _exit(126);
        close(pfd[0]);
        execvp(argv[1], &argv[1]);
        perror("execvp");
        _exit(127);
    }

    // parent: arm counters against the (still-blocked) child
    close(pfd[0]);
    int f_ins = mkcounter(PERF_TYPE_HARDWARE, PERF_COUNT_HW_INSTRUCTIONS,  pid);
    int f_cyc = mkcounter(PERF_TYPE_HARDWARE, PERF_COUNT_HW_CPU_CYCLES,    pid);
    int f_brm = mkcounter(PERF_TYPE_HARDWARE, PERF_COUNT_HW_BRANCH_MISSES, pid);
    int f_chm = mkcounter(PERF_TYPE_HARDWARE, PERF_COUNT_HW_CACHE_MISSES,  pid);

    // release the child
    char go = 1; if (write(pfd[1], &go, 1) < 0) { /* child will EOF and exit */ }
    close(pfd[1]);

    int status = 0;
    waitpid(pid, &status, 0);

    fprintf(stderr, "PERF instructions=%lld cycles=%lld branch-misses=%lld cache-misses=%lld\n",
            readval(f_ins), readval(f_cyc), readval(f_brm), readval(f_chm));

    if (WIFEXITED(status)) return WEXITSTATUS(status);
    return 1;
}
