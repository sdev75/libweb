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

if DEPLOYABLE
deploy:
	@echo -e "$(CINFO)Preparing deploying...$(CNUL)"
	@echo -e "Deploying to $(DEPLOY_USER)@$(DEPLOY_HOST)..."
	rsync -avh --delete --exclude='.git/' \
		"$(abs_builddir)/dist/"\
		$(DEPLOY_USER)@$(DEPLOY_HOST):$(DEPLOY_PATH)
else
deploy:
	echo -e "$(CERR)This project is not deployable...$(CNUL)"
endif

clean:
	@rm -rf "$(abs_builddir)/src/.cache"

env:
	@\
	echo -e "Building $(FBOLD)$@$(CNUL)...";\
	rm -r "$(abs_builddir)/src/.cache/config.env"; \
	mkdir -p "$(abs_builddir)/src/.cache"; \
	in="$(abs_builddir)/src/config.env"; \
	out="$(abs_builddir)/src/.cache/config.env"; \
	ENV_BUF=$(cat $$in | grep -v "=\$$" | grep -v "^DEPLOY_"); \
	php "$(abs_srcdir)/tools/build-env.php" < $$in > $$out; \
	echo -e "Env file saved to: $$out";

lib: env
	@\
	version=$$(cat ${abs_srcdir}/VERSION); \
	mkdir -p $(abs_builddir)/src/.cache/lib; \
	rm -r $(abs_builddir)/src/.cache/lib/libweb-$$version; \
	cp -r $(abs_srcdir)/src $(abs_builddir)/src/.cache/lib/libweb-$$version \
	&& \
	php "$(abs_srcdir)/tools/build-src.php" \
		--env "$(abs_builddir)/src/.cache/config.env" \
		--in "$(abs_builddir)/src/.cache/lib" \
		--out "$(abs_builddir)/src/lib" \
		--include-path "$(abs_builddir)/src" \
	&& \
	rm -r $(abs_builddir)/src/lib/libweb; \
	cp -r $(abs_builddir)/src/lib/libweb-$$version $(abs_builddir)/src/lib/libweb

code: lib
	@\
	echo -e "Building $(FBOLD)$@$(CNUL)..."; \
	rm -r "$(abs_builddir)/src/.cache/code"; \
	php "$(abs_srcdir)/tools/build-code.php" \
		--env "$(abs_builddir)/src/.cache/config.env" \
		--in "$(abs_builddir)/src/code" \
		--out "$(abs_builddir)/dist/code" \
		--include-path "$(abs_builddir)/src"

# entry: code // 	--out "$(abs_builddir)/src/.cache/code" \
# 	@\
# 	echo -e "Building $(FBOLD)$@$(CNUL)..."; \
# 	php "$(abs_srcdir)/tools/build-src.php" \
# 		--env "$(abs_builddir)/src/.cache/config.env" \
# 		--in "$(abs_builddir)/src/entry" \
# 		--out "$(abs_builddir)/dist/entry" \
# 		--include-path "$(abs_builddir)/src"

view: code
	@\
	echo -e "Building $(FBOLD)$@$(CNUL)..."; \
	rm -r "$(abs_builddir)/dist/code"; \
	php "$(abs_srcdir)/tools/build-view.php" \
		--env "$(abs_builddir)/src/.cache/config.env" \
		--in "$(abs_builddir)/src/view" \
		--out "$(abs_builddir)/dist/code" \
		--include-path "$(abs_builddir)/src" \
		--path-code "$(abs_builddir)/dist/code"

public:
	cp -ur $(abs_builddir)/src/public $(abs_builddir)/dist/public

build: view public

run:
	cd $(abs_builddir)/dist && php -S localhost:8080

.PHONY: all clear code entry public env lib run build FORCE
