
import logging
import os
import platform

try:
    from fabric.api import task
    from fabric.api import local
except ModuleNotFoundError:
    from invoke.tasks import task
    from invoke import run as local

############################################################
# Commands to interact with 'hostname';
############################################################

@task
def testdeploy(ctx=None, hostname="localhost"):
    """Deploy the rusa_member module for testing on host 'hostname' (default=localhost)"""
    rsync_command = (
        "rsync -e 'ssh -p555' -avc  --delete-after "
        "--exclude='.git/' --exclude='fabfile.py' --exclude='*.md' "
        ". rusa@{host}:/usr/share/nginx/drupal/web/modules/custom/rusa_member")
    local(rsync_command.format(host=hostname))

