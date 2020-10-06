CNUL=\x1b[0m
COK=\x1b[32;01m
CERR=\x1b[31;01m
CWARN=\x1b[33;01m
CINFO=\x1b[34;01m
CDEBUG=\x1b[90;21m
FBOLD=\x1b[1m
FNORM=\x1b[21m

init: env
	echo -e ""

env:
	clear
	@\
	echo -e "Building $(FBOLD)$@$(CNUL)...";\
	in="$(abs_builddir)/src/config.env"; \
	out="$(abs_builddir)/src/.cache/config.env"; \
	ENV_BUF=$(cat $$in | grep -v "=\$$" | grep -v "^DEPLOY_"); \
	php "$(abs_srcdir)/tools/build-env.php" < $$in > $$out; \
	echo -e "Env file saved to: $$out";

lib:
	@\
	echo -e "Building $(FBOLD)$@$(CNUL)..."; \
	php "$(abs_srcdir)/tools/build-lib.php" \
		--env "$(abs_builddir)/src/.cache/config.env" \
		--in "$(abs_srcdir)/src" \
		--out "$(abs_builddir)/src/.cache/lib" \
	&& \
	php "$(abs_srcdir)/tools/build-src.php" \
		--env "$(abs_builddir)/src/.cache/config.env" \
		--in "$(abs_builddir)/src/.cache/lib" \
		--out "$(abs_builddir)/src/.cache/lib"

code: lib
	@\
	echo -e "Building $(FBOLD)$@$(CNUL)..."; \
	php "$(abs_srcdir)/tools/build-code.php" \
		--env "$(abs_builddir)/src/.cache/config.env" \
		--in "$(abs_builddir)/src/code" \
		--out "$(abs_builddir)/src/.cache/code"

entry: code
	@\
	echo -e "Building $(FBOLD)$@$(CNUL)..."; \
	php "$(abs_srcdir)/tools/build-src.php" \
		--env "$(abs_builddir)/src/.cache/config.env" \
		--in "$(abs_builddir)/src/entry" \
		--out "$(abs_builddir)/dist/entry"

view: code
	@\
	echo -e "Building $(FBOLD)$@$(CNUL)..."; \
	php "$(abs_srcdir)/tools/build-view.php" \
		--env "$(abs_builddir)/src/.cache/config.env" \
		--in "$(abs_builddir)/src/view" \
		--out "$(abs_builddir)/dist/code" \
		--path-code "$(abs_builddir)/src/.cache/code"

public:
	cp -ur $(abs_builddir)/src/public $(abs_builddir)/dist/public

run:
	cd $(abs_builddir)/dist && php -S localhost:8080

.PHONY: all clear code entry public env lib run FORCE
